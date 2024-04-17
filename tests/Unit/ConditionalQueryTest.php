<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
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
     * Проверка условного выражения
     * @return void
     */
    public function testPositiveCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], true]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1', $result);
    }

    /**
     * Проверка пропуска условного выражения
     * @return void
     */
    public function testPositiveSkippedCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], $this->db->skip()]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3)', $result);
    }

    /**
     * Проверка пропуска условного выражения при наличии хотя бы одного skip-маркера
     * @return void
     */
    public function testPositiveSkippedOneArgumentCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d OR id = ?d}', ['user_id', [1, 2, 3], $this->db->skip(), 10]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3)', $result);
    }

    /**
     * Проверка на недостаточное количество аргументов для подстановки
     * @return void
     */
    public function testNegativeInsufficientArgument(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient arguments");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3]]);
    }

    /**
     * Проверка на избыточное количество аргументов для подстановки
     * @return void
     */
    public function testNegativeRedundantArgument(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redundant arguments");
        $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3], true, true]);
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
     * Проверка экранирования скобок
     * @return void
     */
    public function testPositiveEscapeCurlyBraces(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a)/{ AND block = ?d/}', ['user_id', [1, 2, 3], 10]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3){ AND block = 10}', $result);
    }

    /**
     * Проверка экранированных скобок в сочетании с условными выражениями
     * @return void
     */
    public function testPositiveEscapeCurlyBracesCondition(): void
    {
        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a)/{{ AND block = ?d/}}', ['user_id', [1, 2, 3], true]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3){ AND block = 1}', $result);

        $result = $this->db->buildQuery('SELECT name FROM users WHERE ?# IN (?a){/{ AND block = ?d}/}', ['user_id', [1, 2, 3], true]);
        static::assertEquals('SELECT name FROM users WHERE `user_id` IN (1, 2, 3){ AND block = 1}', $result);
    }
}
