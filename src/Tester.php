<?php

namespace mindplay\testies;

use ReflectionFunction;
use TestInterop\Common\AssertionResult;
use TestInterop\TestCase;
use Throwable;
use function debug_backtrace;
use function func_num_args;

// TODO QA: I'm going with this design for now, but will review when it's done.
//          It kinda seems like this class has two responsibilities...
//          addResult() and trace() could possibly move to a separate component,
//          enabling tests (and test-facilities) to implement custom assertions.

// TODO the term "message" seems arbitrary; perhaps "reason" would make more sense?
//      consider changing the terminology here and in the test-interop package

// TODO consider changing the terminology from $value and $expected to
//      $actual and $expected - and in the test-interop package, consider qualifying
//      the accessors as e.g. getActualValue() and getExpectedValue() etc. to clarify
//      the fact that these values are related.

class Tester
{
    /**
     * @var Test
     */
    private $test;

    /**
     * @var TestCase
     */
    private $case;

    public function __construct(Test $test, TestCase $case)
    {
        $this->test = $test;
        $this->case = $case;
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
            $this->addResult($result, __FUNCTION__, [], $message, $value);
        } else {
            $this->addResult($result, __FUNCTION__, [], $message);
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
        $this->addResult($value === $expected, __FUNCTION__, [], $message, $value, $expected);
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
                        $this->addResult(false, __FUNCTION__, [], "$message (message pattern mismatch: {$pattern})", $error);
                        return;
                    }
                }

                $this->addResult(true, __FUNCTION__, [], $message, $error);
            } else {
                $actual_type = get_class($error);

                $this->ok(false, "$message (expected {$exception_type}, but {$actual_type} was thrown)");
            }

            return;
        }

        $this->ok(false, "{$message} (expected {$exception_type}, but no exception was thrown)");
    }

    /**
     * Add an Assertion Result to the Test Case.
     *
     * Note that this function is overloaded: passing a null $value or $expected argument
     * is *not* the same as calling the function without these arguments.
     *
     * @param bool        $result   result of assertion (must === TRUE)
     * @param string      $type     required assertion type (often just the assertion method-name)
     * @param array       $context  additional kay/value map of context values pertaining to the assertion
     * @param string|null $message  optional message describing the reason or expected outcome, etc.
     * @param mixed       $value    actual value (optional)
     * @param mixed       $expected expected value (optional)
     */
    public function addResult(bool $result, string $type, array $context = [], ?string $message = null, $value = null, $expected = null)
    {
        $result = new AssertionResult($result, $type);

        $file = null;
        $line = null;

        [$file, $line] = $this->trace();

        if ($file !== null) {
            $result->setFile($file);
            $result->setLine($line);
        }

        $result->addContext($context);

        $result->setMessage($message);

        if (func_num_args() >= 5) {
            $result->setValue($value);
        }

        if (func_num_args() >= 6) {
            $result->setExpected($expected);
        }

        $this->case->addResult($result);
    }

    /**
     * Obtain a filename and line number to the call that was made in the Test function.
     *
     * @return array where: [string $file, int $line]
     */
    private function trace(): array
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        $test_file = (new ReflectionFunction($this->test->getFunction()))->getFileName();

        for ($i = count($frames); $i--;) {
            $frame = $frames[$i];

            if (@$frame["class"] === TestRunner::class) {
                continue;
            }

            if (@$frame["file"] === $test_file) {
                $file = @$frame["file"];
                $line = @$frame["line"];

                break;
            }
        }

        return [$file, $line];
    }
}
