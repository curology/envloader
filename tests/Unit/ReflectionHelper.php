<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

final class ReflectionHelper
{
    public static function setReflectionProperty($reflectedObject, \ReflectionClass $reflection, string $propertyName, $propertyValue): void
    {
        $propertyReflection = $reflection->getProperty($propertyName);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($reflectedObject, $propertyValue);
    }

    public static function callReflectionMethod($reflectedObject, \ReflectionClass $reflection, string $methodName, array $args)
    {
        $methodReflection = $reflection->getMethod($methodName);
        $methodReflection->setAccessible(true);

        return $methodReflection->invokeArgs($reflectedObject, $args);
    }
}
