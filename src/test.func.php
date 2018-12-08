<?php

namespace mindplay\testies;

use Closure;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

// TODO figure out where to put the reflection facilities

/**
 * Invoke a protected or private method (by means of reflection)
 *
 * @param object $object      the object on which to invoke a method
 * @param string $method_name the name of the method
 * @param array  $arguments   arguments to pass to the function
 *
 * @return mixed the return value from the function call
 */
function invoke($object, string $method_name, array $arguments = [])
{
    $class = new ReflectionClass(get_class($object));

    $method = $class->getMethod($method_name);

    $method->setAccessible(true);

    return $method->invokeArgs($object, $arguments);
}

/**
 * Inspect a protected or private property (by means of reflection)
 *
 * @param object $object        the object from which to retrieve a property
 * @param string $property_name the property name
 *
 * @return mixed the property value
 */
function inspect($object, string $property_name)
{
    $property = new ReflectionProperty(get_class($object), $property_name);

    $property->setAccessible(true);

    return $property->getValue($object);
}
