<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
use Support\Helper\UnitHelper;
use Tests\Support\UnitTester;

/**
 * @covers \pozitronik\FpDbTest\Database::prepareConditionalQuery
 */
class ConditionalQueryTest extends Unit
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
    public function testPositiveCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], true]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1', $result);
    }

    /**
     * @return void
     */
    public function testPositiveSkippedCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], $this->db->skip()]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3)', $result);
    }

    /**
     * @return void
     */
    public function testNegativeInsufficientParameters(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient parameters");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3]]);
    }

    /**
     * @return void
     */
    public function testNegativeNestedOpenCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){{ AND block = ?d}', ['user_id', [1, 2, 3], true]);
    }

    /**
     * @return void
     */
    public function testNegativeNestedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){{ AND block = ?d}}', ['user_id', [1, 2, 3], true]);
    }

    /**
     * @return void
     * todo
     */
    public function testPositiveQuotedCurlyBraces(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a)/{ AND block = ?d/}', ['user_id', [1, 2, 3]]);
        static::assertEquals($result, 'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}');
    }

    /**
     * @return void
     * todo
     */
    public function testNegativeQuotedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unmatched braces");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a)/{{ AND block = ?d/}}', ['user_id', [1, 2, 3], true]);
    }
}
