<?php

namespace mindplay\testies;

use Closure;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

/**
 * Obtain or override the current test-configuration.
 *
 * @param TestConfiguration|null $config optional, custom test-configuration
 *
 * @return TestConfiguration
 */
function configure(TestConfiguration $config = null): TestConfiguration
{
    static $active;

    if ($active === null || $config !== null) {
        $active = $config ?: new TestConfiguration();
    }

    return $active;
}

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
 * Run all queued tests.
 *
 * Typical usage, after configuring your tests with calls to {@see test()}:
 *
 *     exit(run()); // exit with error-level for integration with CI tools, etc.
 *
 * @return int status code (0 on success; 1 on failure)
 */
function run(): int
{
    $success = configure()->driver->run();

    return $success ? 0 : 1;
}

/**
 * Queue a test for running
 *
 * To run all the queued tests (and get the result) call {@see @run()}
 *
 * @param string   $title    test title (short, concise description)
 * @param Closure $function test implementation
 *
 * @return void
 */
function test(string $title, Closure $function)
{
    configure()->driver->addTest($title, $function);
}

/**
 * Set a setup function that is executed before each test.
 *
 * @param Closure $function
 */
function setup(Closure $function)
{
    configure()->driver->setSetup($function);
}

/**
 * Set a teardown function that is executed after each test.
 *
 * @param Closure $function
 */
function teardown(Closure $function)
{
    configure()->driver->setTeardown($function);
}

/**
 * Check and report the result of an expression.
 *
 * @param bool        $result result of assertion (must === TRUE)
 * @param string|null $why    optional description of assertion
 * @param mixed       $value  optional value (displays on failure)
 *
 * @return void
 */
function ok(bool $result, ?string $why = null, $value = null)
{
    configure()->driver->printResult($result, $why, $result === false ? $value : null);
}

/**
 * Compare an actual value against an expected value.
 *
 * @param mixed       $value    actual value
 * @param mixed       $expected expected value (must === $value)
 * @param string|null $why      description of assertion
 *
 * @return void
 */
function eq($value, $expected, ?string $why = null)
{
    $result = $value === $expected;

    configure()->driver->printResult($result, $why, $value, $expected);
}

/**
 * Check for an expected exception, which must be thrown.
 *
 * @param string          $exception_type Exception type name (use `ClassName::class` syntax where possible)
 * @param string          $why            reason for making this assertion
 * @param callable        $function       function expected to cause the exception
 * @param string|string[] $patterns       regular expression pattern(s) to test against the Exception message
 * 
 * @return void
 */
function expect(string $exception_type, string $why, callable $function, $patterns = [])
{
    try {
        $function();
    } catch (Throwable $error) {
        if ($error instanceof $exception_type) {
            foreach ((array) $patterns as $pattern) {
                if (preg_match($pattern, $error->getMessage()) !== 1) {
                    ok(false, "$why (expected {$exception_type} message did not match pattern: {$pattern})", $error);
                    return;
                }
            }

            ok(true, $why, $error);
        } else {
            $actual_type = get_class($error);

            ok(false, "$why (expected {$exception_type} but {$actual_type} was thrown)", $error);
        }

        return;
    }

    ok(false, "{$why} (expected exception {$exception_type} was NOT thrown)");
}

/**
 * Format a value for display (for use in diagnostic messages)
 *
 * @param mixed $value    the value to format for display
 * @param bool  $detailed true to format the value with more detail
 *
 * @return string formatted value
 */
function format($value, bool $detailed = false): string
{
    return configure()->driver->format($value, $detailed);
}

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
