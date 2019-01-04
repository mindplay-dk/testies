<?php

namespace mindplay\testies;

use ErrorException;
use RuntimeException;
use TestInterop\Common\CompositeTestCase;
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
     * @param TestSuite      $suite
     * @param TestListener[] $listeners
     *
     * @return bool true on success, false on failure
     *
     * @throws RuntimeException for unexpected test-failure (only if the `$throw` option is enabled)
     */
    public function run(TestSuite $suite, array $listeners): bool
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

        $result = new TestResult();

        foreach ($suite->getTests() as $test) {
            $case = new CompositeTestCase([
                $result,
                $listener->beginTestCase($test->getName())
            ]);

            $tester = new Tester(new TestResultBuilder($test), $case);

            try {
                // TODO setup?

                // TODO factory abstractions?
                // TODO dependency injection
                call_user_func($test->getFunction(), $tester);

                // TODO teardown?
            } catch (Throwable $error) {
                $case->addError($error);
            }

            $listener->endTestCase();

            if (isset($error) && $this->throw) {
                throw new RuntimeException("Exception while running test: {$test->getName()}", 0, $error);
            }
        }

        $listener->endTestSuite();

        if ($this->strict) {
            restore_error_handler();
        }

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

        // TODO move to console report listener
//        $this->printSummary();

        return ! $result->hasErrors();
    }
}
