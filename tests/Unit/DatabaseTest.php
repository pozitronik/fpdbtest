<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
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

    #todo: add negative cases
}
