<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
use Tests\Support\UnitTester;

/**
 * @covers \FpDbTest\Database
 */
class DatabaseTest extends Unit
{

    protected UnitTester $tester;
    private DatabaseInterface $db;

    /**
     * @Override
     */
    protected function _before(): void
    {
        $this->db = new Database();
    }

    /**
     * @return void
     */
    public function testQueryWithoutTokens(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE user_id = 1');
        static::assertEquals('SELECT name FROM users WHERE user_id = 1', $result);
    }

    /**
     * @return void
     */
    public function testQuerySimpleDefaultToken(): void
    {
        $result = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );
        static::assertEquals('SELECT * FROM users WHERE name = \'Jack\' AND block = 0', $result);
    }

    /**
     * @return void
     */
    public function testQueryFieldNamesTokens(): void
    {
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );
        static::assertEquals('SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1', $result);
    }

    /**
     * @return void
     */
    public function testQueryKeyValuePairsTokens(): void
    {
        $result = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );
        static::assertEquals('UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1', $result);
    }

    /**
     * @return void
     */
    public function testQueryConditional(): void
    {
        $results = [];
        foreach ([null, true] as $block) {
            $results[] = $this->db->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
            );
        }
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3)', $results[0]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1', $results[1]);
    }

    /**
     * Проверка преобразования ?? => ?
     * @return void
     * @throws Exception
     */
    public function testEscapedMarker(): void
    {
        $result = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0 OR name LIKE=\'%??%\'',
            ['Jack']
        );
        static::assertEquals('SELECT * FROM users WHERE name = \'Jack\' AND block = 0 OR name LIKE=\'%?%\'', $result);
    }

    /**
     * Тест ошибки при отключённом экранировании
     * @return void
     * @throws Exception
     */
    public function testNegativeDisableEscapedMarker(): void
    {
        $this->db->allowMarkerEscape = false;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient arguments");
        $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0 OR name LIKE=\'%??%\'',
            ['Jack']
        );

        $result = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0 OR name LIKE=\'%??%\'',
            ['Jack', 'Bill', ' Murray']
        );
        static::assertEquals('SELECT * FROM users WHERE name = \'Jack\' AND block = 0 OR name LIKE=\'%Bill Murray%\'', $result);
        $this->db->allowMarkerEscape = true;
    }

    /**
     * Тест корректной подстановки при отключённом экранировании
     * @return void
     * @throws Exception
     */
    public function testPositiveDisableEscapedMarker(): void
    {
        $this->db->allowMarkerEscape = false;
        $result = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0 OR name IN (??)',
            ['Jack', 'Bill', 'Murray']
        );
        static::assertEquals('SELECT * FROM users WHERE name = \'Jack\' AND block = 0 OR name IN (\'Bill\'\'Murray\')', $result); // это некорректное выражение, но тест проверяет только преобразование, а не SQL-грамматику
        $this->db->allowMarkerEscape = true;
    }

    /**
     * Тест преобразования ассоциативных массивов на маркере идентификатора
     * @return void
     * @throws Exception
     */
    public function testPositiveAssociativeArraysOnIdentifiers(): void
    {
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE ?# AND ?#',
            [['name', 'email'], ['user_id' => 2], ['block' => true]]
        );
        static::assertEquals('SELECT `name`, `email` FROM users WHERE `2` AND `1`', $result);// в задании не оговорено, как должна трактоваться подстановка ?# в случае ассоциативного массива
    }

    /**
     * Тест использования скалярного значения на маркере идентификатора
     * @return void
     * @throws Exception
     */
    public function testPositiveScalarOnIdentifiers(): void
    {
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE ?#',
            [['name', 'email'], true]
        );
        static::assertEquals('SELECT `name`, `email` FROM users WHERE 1', $result);
    }

    /**
     * Тест преобразования ассоциативных массивов на маркере массива
     * @return void
     * @throws Exception
     */
    public function testPositiveAssociativeArraysOnArrayMarker(): void
    {
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE ?a AND ?a',
            [['name', 'email'], ['user_id' => 2], ['block' => true]]
        );
        static::assertEquals('SELECT `name`, `email` FROM users WHERE `user_id` = 2 AND `block` = 1', $result);
    }

    /**
     * Тест использования скалярного значения на маркере массива
     * @return void
     * @throws Exception
     */
    public function testNegativeScalarOnArrayMarker(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Wrong argument type: array expected, got integer");
        $result = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE ?a AND ?a',
            [['name', 'email'], 2, null]
        );
        static::assertEquals('SELECT `name`, `email` FROM users WHERE `user_id` = 2 AND `block` = 1', $result);// это некорректное выражение, но тест проверяет только преобразование, а не SQL-грамматику
    }
}
