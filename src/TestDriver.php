<?php

namespace mindplay\testies;

use Closure;
use Error;
use ErrorException;
use Exception;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report;

/**
 * This class implements the default driver for testing.
 *
 * You can override the default test driver with an extended driver via {@see configure()}.
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
     * @var CodeCoverage|null active code coverage instance (or NULL if inactive)
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
    protected $tests = [];

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
     * @var string title of the last test that generated output
     */
    protected $last_test;

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
        if (isset($this->tests[$title])) {
            throw new RuntimeException("duplicate test name: {$title}");
        }

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
    public function run(): bool
    {
        $this->assertions = 0;
        $this->failures = 0;
        $this->current_test = null;
        $this->last_test = null;

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
            } catch (Error $e) {
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
    public function printTitle(string $title)
    {
        echo "\n=== $title ===\n\n";
    }

    /**
     * Check and report the result of an assertion.
     *
     * @param bool        $result   result of assertion (must === TRUE)
     * @param string|null $why      optional description of assertion
     * @param mixed       $value    optional actual value (displays on failure)
     * @param mixed       $expected optional expected value (displays on failure)
     *
     * @return void
     */
    public function printResult(bool $result, ?string $why = null, $value = null, $expected = null)
    {
        $this->assertions += 1;

        if ($result === false) {
            $this->failures += 1;
        }

        if ($this->verbose === false && $result === true) {
            return; // quite successful assertion
        }

        if ($this->last_test !== $this->current_test) {
            $this->printTitle($this->current_test);

            $this->last_test = $this->current_test;
        }

        $trace = $this->trace();

        $detailed = $result === false;

        $formatted_value = $this->format($value, $detailed);

        $show_diff = $value !== $expected && func_num_args() === 4;

        if ($show_diff) {
            $formatted_expected = $this->format($expected, $detailed);

            $multiline = strpos($formatted_value . $formatted_expected, "\n") !== false;

            if ($multiline) {
                $output = "\n" . trim($this->formatDiff($formatted_value, $formatted_expected), "\r\n");
            } else {
                $output = " ({$formatted_value} !== {$formatted_expected})";
            }
        } else {
            $multiline = strpos($formatted_value, "\n") !== false;

            if ($multiline) {
                $output = "\n" . trim($this->indent($formatted_value), "\r\n");
            } else {
                $output = $value === null
                    ? "" // don't display null when there's no difference from the expected value
                    : " ({$formatted_value})";
            }
        }

        echo ($result === true ? "PASS" : "FAIL")
            . ($trace ? " [{$trace}]" : "")
            . ($why ? " {$why}" : "")
            . $output . "\n";
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
    public function format($value, bool $detailed = false): string
    {
        if ($value instanceof Exception || $value instanceof Error) {
            $details = $value->getMessage();

            if ($detailed) {
                $details .= "\n\nStacktrace:\n" . $value->getTraceAsString();
            }

            return get_class($value) . ":\n{$details}";
        }

        if (! $detailed && is_array($value)) {
            return 'array[' . count($value) . ']';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_object($value) && ! $detailed) {
            return get_class($value);
        }

        return print_r($value, true);
    }

    /**
     * Indents multi-line text for display.
     *
     * @param string $str
     *
     * @return string
     */
    public function indent(string $str): string
    {
        return "  " . implode("\n  ", explode("\n", trim($str))) . "\n";
    }

    /**
     * Obtain a filename and line number index of a call made in a test-closure
     *
     * @return string|null formatted file/line index (or NULL if unable to trace)
     */
    public function trace(): ?string
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
     * @param CodeCoverage $coverage
     *
     * @return void
     */
    public function printCodeCoverageResult(CodeCoverage $coverage)
    {
        $report = new Report\Text(10, 90, false, false);

        echo $report->process($coverage, false);
    }

    /**
     * Output the results of code coverage analysis to an XML file
     *
     * @param CodeCoverage $coverage
     * @param string       $coverage_output_path
     *
     * @return void
     */
    public function outputCodeCoverageReport($coverage, $coverage_output_path)
    {
        $report = new Report\Clover();

        $report->process($coverage, $coverage_output_path);
    }

    const COLOR_RED = "\033[31m";
    const COLOR_GREEN = "\033[32m";
    const COLOR_RESET = "\033[39m";

    /**
     * Renders a color-coded, line-by-line diff of two given multi-line strings.
     * 
     * @param string $old
     * @param string $new
     *
     * @return string
     */
    public function formatDiff(string $old, string $new): string
    {
        $result = "";
        $diff = self::diff(explode("\n", $old), explode("\n", $new));
        
        foreach ($diff as $node) {
            if (is_array($node)) {
                $result .= (! empty($node["d"]) ? self::COLOR_RED . "+ " . implode("\n", $node["d"]) : "") .
                    (! empty($node["i"]) ? self::COLOR_GREEN . "- " . implode("\n", $node["i"]) : "")
                    . "\n";
            } else {
                $result .= self::COLOR_RESET . "  " . $node . "\n";
            }
        }
        
        return $result . self::COLOR_RESET;
    }

    /**
     * @param string[] $old
     * @param string[] $new
     *
     * @return string|array mixed list of unchanged strings and tuples where "d" and "i" => deleted/inserted strings
     */
    private static function diff(array $old, array $new)
    {
        // https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
        
        $matrix = [];
        $maxlen = 0;

        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);

            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1])
                    ? $matrix[$oindex - 1][$nindex - 1] + 1
                    : 1;

                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];

                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }

        if ($maxlen === 0) {
            return [["d" => $old, "i" => $new]];
        }

        return array_merge(
            self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }
}
