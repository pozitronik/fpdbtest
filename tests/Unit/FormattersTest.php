<?php


namespace Tests\Unit;

use Codeception\Test\Unit;
use pozitronik\FpDbTest\Database;
use pozitronik\FpDbTest\DatabaseInterface;
use ReflectionException;
use Tests\Support\UnitTester;

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
    protected function _before() : void
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
}
