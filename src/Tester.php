<?php

namespace mindplay\testies;

use TestInterop\Common\AssertionResult;
use TestInterop\TestCase;
use Throwable;

class Tester
{
    /**
     * @var TestResultBuilder
     */
    private $builder;

    /**
     * @var TestCase
     */
    private $case;

    public function __construct(TestResultBuilder $builder, TestCase $case)
    {
        $this->case = $case;
        $this->builder = $builder;
    }

    /**
     * Check and report the result of an expression.
     *
     * @param bool        $result result of assertion (must === TRUE)
     * @param string|null $why    optional description of assertion
     * @param mixed       $value  optional value (displays on failure)
     */
    public function ok($result, ?string $why = null, $value = null): void
    {
        $_result = new AssertionResult($result, __FUNCTION__);

        if ($why !== null) {
            $_result->setMessage($why);
        }

        if (func_num_args() === 3) {
            $_result->setValue($value);
        }

        $this->case->addResult($_result);
    }

    /**
     * Compare an actual value against an expected value.
     *
     * @param mixed       $value    actual value
     * @param mixed       $expected expected value (must === $value)
     * @param string|null $why      description of assertion
     */
    public function same($value, $expected, ?string $why = null): void
    {
        $result = new AssertionResult($value === $expected, __FUNCTION__);

        $result->setValue($value);

        $result->setExpected($expected);

        if ($why !== null) {
            $result->setMessage($why);
        }

        $this->case->addResult($result);
    }

    /**
     * Check for an expected exception, which must be thrown.
     *
     * @param string          $exception_type Exception type name (use `ClassName::class` syntax where possible)
     * @param string          $why            reason for making this assertion
     * @param callable        $function       function expected to cause the exception
     * @param string|string[] $patterns       regular expression pattern(s) to test against the Exception message
     */
    public function expect(string $exception_type, string $why, callable $function, $patterns = []): void
    {
        $result = false;
        // TODO fix everything

        $error = null;

        try {
            call_user_func($function);
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

                ok(false, "$why (expected {$exception_type} but {$actual_type} was thrown)");
            }

            return;
        }

        ok(false, "{$why} (expected exception {$exception_type} was NOT thrown)");
    }
}
