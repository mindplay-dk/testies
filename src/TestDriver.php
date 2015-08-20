<?php

namespace mindplay\testies;

use PHP_CodeCoverage;
use PHP_CodeCoverage_Report_Text;
use PHP_CodeCoverage_Report_Clover;

use Exception;
use ErrorException;
use Closure;
use RuntimeException;

/**
 * This class implements the default driver for testing.
 *
 * You can override the default test driver with an extended driver via {@link configure()}.
 *
 * To access the driver (e.g. from custom assertion functions) use `configure()->driver->...`
 */
class TestDriver
{
    /**
     * @var bool true to enable verbose output
     */
    public $verbose = false;

    /**
     * @var bool true to enable strict error handling
     */
    public $strict = true;

    /**
     * @var PHP_CodeCoverage|null active code coverage instance (or NULL if inactive)
     */
    public $coverage;

    /**
     * @var bool true to enable throwing of unexpected exceptions in tests (useful for debugging)
     */
    public $throw = false;

    /**
     * @var string absolute path to code coverage report output file (e.g. "clover.xml")
     */
    public $coverage_output_path = null;

    /**
     * @var Closure[] map where test title => test function
     */
    protected $tests = array();

    /**
     * @var int total number of assertions performed
     */
    protected $assertions = 0;

    /**
     * @var int number of failed assertions
     */
    protected $failures = 0;

    /**
     * @var string title the test currently being run
     */
    protected $current_test;

    /**
     * @var string title of the test that last generated output
     */
    protected $last_output;

    /**
     * @var Closure
     */
    protected $setup;

    /**
     * @var Closure
     */
    protected $teardown;

    /**
     * @param string  $title    test title (short, concise description)
     * @param Closure $function test implementation
     *
     * @return $this
     *
     * @see test()
     */
    public function addTest($title, Closure $function)
    {
        $this->tests[$title] = $function;

        return $this;
    }

    /**
     * @param Closure $function
     */
    public function setSetup(Closure $function)
    {
        $this->setup = $function;
    }

    /**
     * @param Closure $function
     */
    public function setTeardown(Closure $function)
    {
        $this->teardown = $function;
    }

    /**
     * Run all queued tests
     *
     * @return bool true if all tests succeed; otherwise false
     */
    public function run()
    {
        $this->assertions = 0;
        $this->failures = 0;
        $this->current_test = null;
        $this->last_output = null;

        if ($this->strict) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                $error = new ErrorException($errstr, 0, $errno, $errfile, $errline);

                if ($error->getSeverity() & error_reporting()) {
                    throw $error;
                }
            });
        }

        if ($this->coverage) {
            $this->coverage->start('test');
        }

        foreach ($this->tests as $title => $function) {
            $this->current_test = $title;

            $thrown = null;

            try {
                if ($this->setup) {
                    call_user_func($this->setup);
                }

                call_user_func($function);

                if ($this->teardown) {
                    call_user_func($this->teardown);
                }
            } catch (Exception $e) {
                $this->printResult(false, "UNEXPECTED EXCEPTION", $e);

                $thrown = $e;
            }

            if ($thrown && $this->throw) {
                throw new Exception("Exception while running test: {$title}", 0, $thrown);
            }
        }

        if ($this->coverage) {
            $this->coverage->stop();
        }

        if ($this->strict) {
            restore_error_handler();
        }

        $this->current_test = null;

        if ($this->coverage) {
            $this->printCodeCoverageResult($this->coverage);

            if ($this->coverage_output_path) {
                $this->outputCodeCoverageReport($this->coverage, $this->coverage_output_path);

                echo "\n* code coverage report created: {$this->coverage_output_path}\n";
            }
        }

        $this->printSummary();

        return $this->failures === 0;
    }

    /**
     * Print the title of the test being executed
     *
     * @param string $title
     *
     * @return void
     */
    public function printTitle($title)
    {
        echo "\n=== $title ===\n\n";
    }

    /**
     * Check and report the result of an expression.
     *
     * @param bool   $result result of assertion (must === TRUE)
     * @param string $why    description of assertion
     * @param mixed  $value  optional value (displays on failure)
     *
     * @return void
     */
    public function printResult($result, $why = null, $value = null)
    {
        $this->assertions += 1;

        if ($result === false) {
            $this->failures += 1;
        }

        if ($this->verbose === false && $result === true) {
            return; // be quiet.
        }

        if ($this->last_output !== $this->current_test) {
            $this->printTitle($this->current_test);

            $this->last_output = $this->current_test;
        }

        $trace = $this->trace();

        if ($trace) {
            $trace = "[{$trace}] ";
        }

        $formatted = '';

        if ($value !== null) {
            $formatted = $this->format($value, $result === false);

            $formatted = strpos($formatted, "\n") === false
                ? "({$formatted})"
                : "-> {$formatted}";
        }

        if ($result === true) {
            echo "- PASS: {$trace}" . ($why ?: 'OK') . " {$formatted}\n";
        } else {
            echo "# FAIL: {$trace}" . ($why ?: 'ERROR') . " {$formatted}\n";
        }
    }

    /**
     * Print summary results after completing a test run
     */
    public function printSummary()
    {
        $tests = count($this->tests);

        echo "\n* {$tests} tests completed: {$this->assertions} assertions, {$this->failures} failures\n";
    }

    /**
     * Format a value for display (for use in diagnostic messages)
     *
     * @param mixed $value    the value to format for display
     * @param bool  $detailed true to format the value with more detail
     *
     * @return string formatted value
     */
    public function format($value, $detailed = false)
    {
        if ($value instanceof Exception) {
            return $detailed
                ? get_class($value) . ": \n\"" . $value->getMessage() . "\"\n\nStacktrace:\n" . $value->getTraceAsString()
                : get_class($value) . ": \n\"" . $value->getMessage() . "\"";
        }

        if (!$detailed && is_array($value)) {
            return 'array[' . count($value) . ']';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_object($value) && !$detailed) {
            return get_class($value);
        }

        return print_r($value, true);
    }

    /**
     * Obtain a filename and line number index of a call made in a test-closure
     *
     * @return string|null formatted file/line index (or NULL if unable to trace)
     */
    public function trace()
    {
        $traces = debug_backtrace();

        $current_function = $this->tests[$this->current_test];

        $skip = 0;

        $found = false;

        while (count($traces)) {
            $trace = array_pop($traces);

            if ($skip > 0) {
                $skip -= 1;
                continue; // skip closure
            }

            if (($trace['file'] === __FILE__) && (@$trace['args'][0] === $current_function)) {
                $skip = 1;
                $found = true;
                continue; // skip call to run()
            }

            if ($found && isset($trace['file'])) {
                return basename($trace['file']) . '#' . $trace['line'];
            }
        }

        return null;
    }

    /**
     * Print the results of code coverage analysis to the console
     *
     * @param PHP_CodeCoverage $coverage
     *
     * @return void
     */
    public function printCodeCoverageResult(PHP_CodeCoverage $coverage)
    {
        $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);

        echo $report->process($coverage, false);
    }

    /**
     * Output the results of code coverage analysis to an XML file
     *
     * @param PHP_CodeCoverage $coverage
     * @param string           $coverage_output_path
     *
     * @return void
     */
    public function outputCodeCoverageReport($coverage, $coverage_output_path)
    {
        $report = new PHP_CodeCoverage_Report_Clover();

        $report->process($coverage, $coverage_output_path);
    }
}
