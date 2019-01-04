<?php

/**
 * This file defines global functions.
 *
 * These functions either have no dependencies, or depend only on PHP globals or environment,
 * and as such there's no reason for any of these to be methods of any object or class.
 */

namespace mindplay\testies;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Tests if a given option is enabled on the command-line.
 *
 * For example, `if (enabled('skip-slow'))` checks for a `--skip-slow` option.
 *
 * @param string $option    option name
 * @param string $shorthand single-letter shorthand (optional)
 *
 * @return bool TRUE, if the specified option was enabled on the command-line
 */
function enabled(string $option, string $shorthand = ""): bool
{
    return in_array(getopt($shorthand, [$option]), [[$option => false], [$shorthand => false]], true);
}

/**
 * Invoke a protected or private method (by means of reflection)
 *
 * @param object $object      the object on which to invoke a method
 * @param string $method_name the name of the method
 * @param array  $arguments   arguments to pass to the function
 *
 * @return mixed the return value from the function call
 *
 * @throws ReflectionException on failure
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
 *
 * @throws ReflectionException on failure
 */
function inspect($object, string $property_name)
{
    $property = new ReflectionProperty(get_class($object), $property_name);

    $property->setAccessible(true);

    return $property->getValue($object);
}
