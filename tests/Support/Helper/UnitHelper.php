<?php
declare(strict_types=1);

namespace Support\Helper;

use ReflectionClass;
use ReflectionException;

/**
 * Helpers for unit tests
 */
class UnitHelper
{

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
}