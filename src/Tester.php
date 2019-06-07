<?php

namespace mindplay\testies;

use Throwable;
use function func_num_args;

/**
 * This class implements basic assertions.
 */
class Tester
{
    /**
     * @var TestResult
     */
    private $result;

    public function __construct(TestResult $result)
    {
        $this->result = $result;
    }

    /**
     * Check and report the result of an assertion.
     *
     * @param bool        $result  result of assertion
     * @param string|null $message optional message describing the reason or expected outcome, etc.
     * @param mixed       $value   optional value (displays on failure)
     */
    public function ok(bool $result, ?string $message = null, $value = null): void
    {
        if (func_num_args() === 3) {
            $this->result->add($result, __FUNCTION__, [], $message, $value);
        } else {
            $this->result->add($result, __FUNCTION__, [], $message);
        }
    }

    /**
     * Compare an actual value against an expected value.
     *
     * @param mixed       $value    actual value
     * @param mixed       $expected expected value (must === $value)
     * @param string|null $message  optional message describing the reason or expected outcome, etc.
     */
    public function eq($value, $expected, ?string $message = null): void
    {
        $this->result->add($value === $expected, __FUNCTION__, [], $message, $value, $expected);
    }

    /**
     * Check for an expected exception, which must be thrown.
     *
     * @param string          $exception_type Exception type name (use `ClassName::class` syntax where possible)
     * @param string          $message        optional message describing the reason or expected outcome, etc.
     * @param callable        $function       function expected to cause the exception
     * @param string|string[] $patterns       regular expression pattern(s) to test against the Exception message
     */
    public function expect(string $exception_type, string $message, callable $function, $patterns = []): void
    {
        try {
            call_user_func($function);
        } catch (Throwable $error) {
            if ($error instanceof $exception_type) {
                foreach ((array) $patterns as $pattern) {
                    if (preg_match($pattern, $error->getMessage()) !== 1) {
                        $this->result->add(false, __FUNCTION__, [], "$message (message pattern mismatch: {$pattern})", $error);

                        return;
                    }
                }

                $this->result->add(true, __FUNCTION__, [], $message, $error);
            } else {
                $actual_type = get_class($error);

                $this->ok(false, "$message (expected {$exception_type}, but {$actual_type} was thrown)");
            }

            return;
        }

        $this->ok(false, "{$message} (expected {$exception_type}, but no exception was thrown)");
    }
}
