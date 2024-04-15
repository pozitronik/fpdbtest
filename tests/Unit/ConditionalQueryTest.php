<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
use ReflectionException;
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
     * @throws ReflectionException
     */
    public function testPositiveCondition(): void
    {
        $result = UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], true]]);
        static::assertEquals($result, 'SELECT name FROM users WHERE ?# IN (?a) AND block = ?d');
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testPositiveSkippedCondition(): void
    {
        $result = UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], $this->db->skip()]]);
        static::assertEquals($result, 'SELECT name FROM users WHERE ?# IN (?a)');
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeUnmatchedValues(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Parameters count doesn''t match");
        UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3]]]);

    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeUnmatchedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unmatched braces");
        UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a){{ AND block = ?d}', ['user_id', [1, 2, 3], true]]);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeNestedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a){{ AND block = ?d}}', ['user_id', [1, 2, 3], true]]);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testPositiveQuotedCurlyBraces(): void
    {
        $result = UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a)/{ AND block = ?d/}', ['user_id', [1, 2, 3]]]);
        static::assertEquals($result, 'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}');
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeQuotedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unmatched braces");
        UnitHelper::invokePrivateMethod($this->db, 'prepareConditionalQuery', ['SELECT name FROM users WHERE ?# IN (?a)/{{} AND block = ?d/}', ['user_id', [1, 2, 3], true]]);
    }
}
