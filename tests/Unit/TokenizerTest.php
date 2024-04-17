<?php
declare(strict_types=1);


use Codeception\Test\Unit;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
use Tests\Support\UnitTester;

/**
 * @covers \pozitronik\FpDbTest\Database::tokenizeQuery
 */
class TokenizerTest extends Unit
{

    protected UnitTester $tester;
    private DatabaseInterface $db;

    /**
     * Вызывает закрытый метод класса
     * @param object $theClass
     * @param string $methodName
     * @param array $args
     * @return mixed
     * @throws ReflectionException
     */
    public static function invokePrivateMethod(object $theClass, string $methodName, array $args): mixed
    {
        if (null === $class = new ReflectionClass($theClass)) return null;
        return $class->getMethod($methodName)->invokeArgs($theClass, $args);
    }

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
        $result = static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}']);
        static::assertEquals($result, [
            [
                'condition' => false,
                'value' => 'SELECT name FROM users WHERE ?# IN (?a)'
            ], [
                'condition' => true,
                'value' => ' AND block = ?d'
            ]
        ]);
        $result = static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}{ AND block = ?d}']);
        static::assertEquals($result, [
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

        $result = static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}{ AND block = ?d} OR id IS NULL']);
        static::assertEquals($result, [
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
        static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a)}}{ AND block = ?d}']);

    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeNestedCurlyBraces(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {?# IN (?a){ AND block = ?d}']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Nested conditional expression");
        static::invokePrivateMethod($this->db, 'tokenizeQueryConditions', ['SELECT name FROM users WHERE {{?# IN (?a)}}{ AND block = ?d}']);
    }
}
