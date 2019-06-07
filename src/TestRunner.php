<?php

namespace mindplay\testies;

use ErrorException;
use ReflectionFunction;
use RuntimeException;
use TestInterop\Common\CompositeTestListener;
use TestInterop\TestListener;
use Throwable;

class TestRunner
{
    /**
     * @var bool true to enable strict error handling
     */
    public $strict = true;

    /**
     * @var bool true to enable throwing of unexpected exceptions in tests (useful for debugging)
     */
    public $throw = false;

    /**
     * Runs a given test-suite with a given array of listeners.
     *
     * Returns an `int` error-level value, e.g. `0` for success or `1` for failure,
     * intended for use with an `exit()` statement.
     *
     * @param TestSuite      $suite
     * @param TestListener[] $listeners
     *
     * @return int error-level (0 for success, 1 for failure.)
     *
     * @throws RuntimeException for unexpected test-failure (only if the `$throw` option is enabled)
     */
    public function run(TestSuite $suite, array $listeners): int
    {
        // TODO add a built-in listener (maybe?) to check whether the test-suite passed/failed?

        $listener = new CompositeTestListener($listeners);

        if ($this->strict) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                $error = new ErrorException($errstr, 0, $errno, $errfile, $errline);

                if ($error->getSeverity() & error_reporting()) {
                    throw $error;
                }
            });
        }

        $listener->beginTestSuite($suite->getName(), $suite->getProperties());

        $status = 0;

        foreach ($suite->getTests() as $test) {
            $result = new TestResult($test, $listener);

            $reflection = new ReflectionFunction($test->getFunction());

            $source = $reflection->getFileName()
                . "(" . $reflection->getStartLine()
                . ".." . $reflection->getEndLine()
                . ")";

            $listener->beginTestCase($test->getName(), $source);

            try {
                // TODO setup?

                // TODO factory abstractions?
                // TODO dependency injection!
                call_user_func($test->getFunction(), new Tester($result), $result);

                // TODO teardown?
            } catch (Throwable $error) {
                $listener->setError($error);

                $status = 1;
            }

            $listener->endTestCase();

            if ($result->hasErrors()) {
                $status = 1;
            }

            if (isset($error) && $this->throw) {
                throw new RuntimeException("Exception while running test: {$test->getName()}", 0, $error);
            }
        }

        $listener->endTestSuite();

        if ($this->strict) {
            restore_error_handler();
        }

        return $status;

        // TODO move to coverage listener
//        if ($this->coverage) {
//            $this->printCodeCoverageResult($this->coverage);
//
//            if ($this->coverage_output_path) {
//                $this->outputCodeCoverageReport($this->coverage, $this->coverage_output_path);
//
//                echo "\n* code coverage report created: {$this->coverage_output_path}\n";
//            }
//        }
    }
}
