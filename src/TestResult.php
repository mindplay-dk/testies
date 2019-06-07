<?php

namespace mindplay\testies;

use function count;
use function debug_backtrace;
use function func_num_args;
use ReflectionFunction;
use TestInterop\Common\AssertionResult;
use TestInterop\TestListener;
use Throwable;

// TODO the term "message" seems arbitrary; perhaps "reason" would make more sense?
//      consider changing the terminology here and in the test-interop package

// TODO consider changing the terminology from $value and $expected to
//      $actual and $expected - and in the test-interop package, consider qualifying
//      the accessors as e.g. getActualValue() and getExpectedValue() etc. to clarify
//      the fact that these values are related.

/**
 * This class represents the result of running a test-case.
 */
class TestResult
{
    /**
     * @var Test
     */
    private $test;

    /**
     * @var TestListener
     */
    private $listener;

    /**
     * @var bool
     */
    private $has_errors = false;

    public function __construct(Test $test, TestListener $listener)
    {
        $this->test = $test;
        $this->listener = $listener;
    }

    public function hasErrors(): bool
    {
        return $this->has_errors;
    }

    /**
     * Add an Assertion Result to the Test Case.
     *
     * Note that this function is overloaded: passing a null $value or $expected argument
     * is *not* the same as calling the function without these arguments.
     *
     * @param bool        $result   result of assertion (true = passed, false = failed)
     * @param string      $type     required assertion type (often just the assertion method-name)
     * @param array       $context  additional kay/value map of context values pertaining to the assertion
     * @param string|null $message  optional message describing the reason or expected outcome, etc.
     * @param mixed       $value    actual value (optional)
     * @param mixed       $expected expected value (optional)
     */
    public function add(
        bool $result,
        string $type,
        array $context = [],
        ?string $message = null,
        $value = null,
        $expected = null
    ) {
        if ($result === false) {
            $this->has_errors = true;
        }

        $_result = new AssertionResult($result, $type);

        $file = null;
        $line = null;

        [$file, $line] = $this->trace();

        if ($file !== null) {
            $_result->setFile($file);
            $_result->setLine($line);
        }

        $_result->addContext($context);

        $_result->setMessage($message);

        if (func_num_args() >= 5) {
            $_result->setValue($value);
        }

        if (func_num_args() >= 6) {
            $_result->setExpected($expected);
        }

        $this->listener->addResult($_result);
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
