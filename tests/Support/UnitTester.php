<?php

declare(strict_types=1);

namespace Tests\Support;

use Codeception\Actor;
use ReflectionClass;
use ReflectionException;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends Actor
{
    use _generated\UnitTesterActions;

    /**
     * Вызывает закрытый метод класса
     * @param object $theClass
     * @param string $methodName
     * @param array $args
     * @return mixed
     * @throws ReflectionException
     */
    public function invokePrivateMethod(object $theClass, string $methodName, array $args): mixed
    {
        if (null === $class = new ReflectionClass($theClass)) return null;
        return $class->getMethod($methodName)->invokeArgs($theClass, $args);
    }
}
