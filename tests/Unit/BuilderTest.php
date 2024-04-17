<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use pozitronik\FpDbTest\Database;
use Tests\Support\UnitTester;

/**
 *
 */
class BuilderTest extends Unit
{

    protected UnitTester $tester;
    private Database $db;

    /**
     * @Override
     */
    protected function _before()
    {
        $this->db = new Database();
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
