<?php

namespace mindplay\testies;

use function func_num_args;
use TestInterop\Common\AssertionResult;
use TestInterop\TestCase;
use Throwable;

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
                        $this->addResult(false, __FUNCTION__, [], "$message (expected {$exception_type} message did not match pattern: {$pattern})", $error);
                        return;
                    }
                }

                $this->addResult(true, __FUNCTION__, [], $message, $error);
            } else {
                $actual_type = get_class($error);

                $this->ok(false, "$message (expected {$exception_type} but {$actual_type} was thrown)");
            }

            return;
        }

        $this->ok(false, "{$message} (expected exception {$exception_type} was NOT thrown)");
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
    protected function addResult(bool $result, string $type, array $context = [], ?string $message = null, $value = null, $expected = null)
    {
        $result = new AssertionResult($result, $type);

        $file = null;
        $line = null;

        $this->trace($file, $line);

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
     * @param string|null &$file
     * @param int|null    &$line
     */
    private function trace(?string &$file, ?int &$line): void
    {
        $traces = debug_backtrace();

        $skip = 0;

        $found = false;

        while (count($traces)) {
            $trace = array_pop($traces);

            if ($skip > 0) {
                $skip -= 1;
                continue; // skip closure
            }

            if (($trace['file'] ?? null === __FILE__) && (@$trace['args'][0] === $this->test->getFunction())) {
                $skip = 1;
                $found = true;
                continue; // skip call to run()
            }

            if ($found && isset($trace['file'])) {
                $file = $trace['file'];
                $line = $trace['line'];
            }
        }

// TODO implement this simplified prototype
//        $trace = debug_backtrace();
//
//        for ($i=count($trace); $i--;) {
//            $entry = $trace[$i];
//
//            if (@$entry['class'] === TestRunner::class && @$entry['function'] === "run") {
//                if (isset($trace[$i-2])) {
//                    $found = $trace[$i-2];
//
//                    return "{$found['file']}:{$found['line']} ({$found['class']}::{$found['function']})";
//                }
//            }
//        }
    }
}
