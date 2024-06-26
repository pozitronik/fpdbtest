<?php
declare(strict_types=1);


use Codeception\Test\Unit;
use FpDbTest\Database;
use FpDbTest\DatabaseInterface;
use Tests\Support\UnitTester;

/**
 * @covers \FpDbTest\Database::tokenizeQueryConditions
 */
class TokenizerTest extends Unit
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
        $result = $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}']);
        $this->tester->assertEquals($result, [
            [
                'condition' => false,
                'value' => 'SELECT name FROM users WHERE ?# IN (?a)'
            ], [
                'condition' => true,
                'value' => ' AND block = ?d'
            ]
        ]);
        $result = $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}{ AND block = ?d}']);
        $this->tester->assertEquals($result, [
            [
                'condition' => false,
                'value' => 'SELECT name FROM users WHERE '
            ], [
                'condition' => true,
                'value' => '?# IN (?a)'
            ], [
                'condition' => true,
                'value' => ' AND block = ?d'
            ]
        ]);

        $result = $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}{ AND block = ?d} OR id IS NULL']);
        $this->tester->assertEquals($result, [
            [
                'condition' => false,
                'value' => 'SELECT name FROM users WHERE '
            ], [
                'condition' => true,
                'value' => '?# IN (?a)'
            ], [
                'condition' => true,
                'value' => ' AND block = ?d'
            ], [
                'condition' => false,
                'value' => ' OR id IS NULL'
            ]
        ]);
    }


    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeUnmatchedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unmatched braces");
        $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}}{ AND block = ?d}']);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeNestedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a){ AND block = ?d}']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        $this->tester->invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {{?# IN (?a)}}{ AND block = ?d}']);
    }
}
