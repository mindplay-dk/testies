<?php

namespace mindplay\testies;

use ErrorException;
use Exception;
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
     * Tests if a given option is enabled on the command-line.
     *
     * For example, `if (enabled('skip-slow'))` checks for a `--skip-slow` option.
     *
     * @param string $option    option name
     * @param string $shorthand single-letter shorthand (optional)
     *
     * @return bool TRUE, if the specified option was enabled on the command-line
     */
    public function enabled(string $option, string $shorthand = ""): bool
    {
        return in_array(getopt($shorthand, [$option]), [[$option => false], [$shorthand => false]], true);
    }

    /**
     * @param TestSuite      $suite
     * @param TestListener[] $listeners
     *
     * @return bool true on success, false on failure
     */
    public function run(TestSuite $suite, array $listeners): bool
    {
        // TODO add a built-in listener (maybe?) to check whether the test-suite passed/failed?

        $test_listener = new CompositeTestListener($listeners);

        if ($this->strict) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                $error = new ErrorException($errstr, 0, $errno, $errfile, $errline);

                if ($error->getSeverity() & error_reporting()) {
                    throw $error;
                }
            });
        }

        $suite_listener = $test_listener->beginTestSuite($suite->getName(), $suite->getProperties());

        foreach ($suite->getTestCases() as $case) {
            $case_listener = $suite_listener->beginTestCase($case->getName());

            $thrown = null;

            try {
                // TODO setup?

                // TODO factory abstractions?
                // TODO dependency injection
                call_user_func($case->getFunction(), new Tester(new TestResultBuilder($case), $case_listener));

                // TODO teardown?
            } catch (Throwable $error) {
                $case_listener->addError($error);

                $thrown = $error;
            }

            if ($thrown && $this->throw) {
                throw new Exception("Exception while running test: {$case->getName()}", 0, $thrown);
            }
        }

        $case_listener->end();

        if ($this->strict) {
            restore_error_handler();
        }

        // TODO move to coverage listener
        if ($this->coverage) {
            $this->printCodeCoverageResult($this->coverage);

            if ($this->coverage_output_path) {
                $this->outputCodeCoverageReport($this->coverage, $this->coverage_output_path);

                echo "\n* code coverage report created: {$this->coverage_output_path}\n";
            }
        }

        // TODO test-interop needs some way to signal the end of the test-suite
        //      (and probably some way to signal the end of the entire test?)

        // TODO move to console report listener
        $this->printSummary();
    }
}
