<?php
declare(strict_types=1);


namespace Tests\Unit;

use Codeception\Test\Unit;
use FpDbTest\Database;
use FpDbTest\DatabaseInterface;
use ReflectionException;
use Tests\Support\UnitTester;
use TypeError;

/**
 * Тесты форматтеров
 */
class FormattersTest extends Unit
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
    public function testFormatScalar(): void
    {
        $this->tester->assertEquals("'abc'", $this->tester->invokePrivateMethod($this->db, 'formatScalar', ["abc"]));
        $this->tester->assertEquals("`abc`", $this->tester->invokePrivateMethod($this->db, 'formatScalar', ["abc", true]));
        $this->tester->assertEquals(1, $this->tester->invokePrivateMethod($this->db, 'formatScalar', [1]));
        $this->tester->assertEquals(3.14, $this->tester->invokePrivateMethod($this->db, 'formatScalar', [3.14]));
        $this->tester->assertEquals(1, $this->tester->invokePrivateMethod($this->db, 'formatScalar', [true]));
        $this->tester->assertEquals(0, $this->tester->invokePrivateMethod($this->db, 'formatScalar', [false]));
        $this->tester->assertEquals("NULL", $this->tester->invokePrivateMethod($this->db, 'formatScalar', [null]));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testFormatScalarOnArray(): void
    {
        $this->expectException(TypeError::class);
        $this->tester->invokePrivateMethod($this->db, 'formatScalar', [["abc"]]);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testFormatArray(): void
    {
        $this->tester->assertEquals("'abc'", $this->tester->invokePrivateMethod($this->db, 'formatArray', [["abc"]]));
        $this->tester->assertEquals("`abc` = 1, `def` = 32.1", $this->tester->invokePrivateMethod($this->db, 'formatArray', [["abc" => true, "def" => 32.1]]));
        $this->tester->assertEquals("'abc', 'def', 32.1", $this->tester->invokePrivateMethod($this->db, 'formatArray', [["abc", "def", 32.1]]));
        $this->tester->assertEquals("`abc` IN ('def', 32.1, 1)", $this->tester->invokePrivateMethod($this->db, 'formatArray', [["abc" => ["def", 32.1, true]]]));
        $this->tester->assertEquals("`abc` IN (32.1)", $this->tester->invokePrivateMethod($this->db, 'formatArray', [["abc" => ["def" => 32.1]]]));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNegativeFormatArray(): void
    {
        $this->expectException(TypeError::class);
        $this->tester->invokePrivateMethod($this->db, 'formatArray', ['string value']);
        $this->expectException(TypeError::class);
        $this->tester->invokePrivateMethod($this->db, 'formatArray', [[["abc", "def", 32.1]]]); //непредусмотренный случай вложенного массива
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testFormatIdentifier(): void
    {
        $this->tester->assertEquals("`abc`", $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', ["abc"]));
        $this->tester->assertEquals(1, $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [1]));
        $this->tester->assertEquals(3.14, $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [3.14]));
        $this->tester->assertEquals(1, $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [true]));
        $this->tester->assertEquals(0, $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [false]));
        $this->tester->assertEquals("NULL", $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [null]));
        $this->tester->assertEquals("`abc`, `def`", $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [["abc", "def"]]));
        $this->tester->assertEquals("`def`", $this->tester->invokePrivateMethod($this->db, 'formatIdentifier', [["abc" => "def"]]));
    }
}
