<?php

namespace mindplay\testies;

use Closure;
use ErrorException;
use Exception;
use ReflectionFunction;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use Throwable;

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
                    ($this->setup)();
                }
        
                $function();
        
                if ($this->teardown) {
                    ($this->teardown)();
                }
            } catch (Throwable $e) {
                $this->printError($e);
    
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

                $coverage_output_path = readable::path($this->coverage_output_path);

                echo "\n* code coverage report created: {$coverage_output_path}\n";
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

        $location = $this->trace();

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
            . (" {$location}")
            . ($why ? ": {$why}" : ":")
            . $output . "\n";
    }

    public function printError(Throwable $error)
    {
        echo "ERROR\n" . $this->indent($this->formatError($error));
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
        if ($value instanceof Throwable) {
            return $this->formatError($value, $detailed);
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

        if (is_string($value)) {
            $lines = explode("\n", $value);
            $lines = array_map([$this, "escape"], $lines);
            $value = implode("\n", $lines);
        }

        return print_r($value, true);
    }

    public function formatError(Throwable $error, bool $detailed = true): string
    {
        $details = $error->getMessage();

        if ($detailed) {
            $trace = $error->getTrace();

            // TODO filter out testies internals? maybe optional?
            // $trace = array_values(array_filter($trace, fn ($frame) => ! str_starts_with($frame['file'] ?? "", __DIR__)));

            $details .= "\n\nStacktrace:\n" . $this->indent(readable::trace($trace, with_params: true, relative_paths: true)) . "\n";
        }

        return get_class($error) . ": {$details}";
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
     * Escape non-printable ASCII control codes for display
     */
    public function escape(string $str): string
    {
        return preg_replace_callback(
            '/([\x00\x07\x08\x09\x0A\x0B\x0C\x0D\x1B\x7F])/u',
            function ($matches) {
                return '\\x' . strtoupper(dechex(ord($matches[1])));
            },
            $str
        );
    }

    /**
     * Obtain a filename and line number index of a call made in the current test-closure
     *
     * @return string formatted file/line index
     */
    public function trace(): string
    {
        $traces = debug_backtrace(0);

        $current_function = $this->tests[$this->current_test];

        $current_file = (new ReflectionFunction($current_function))->getFileName();

        $selected_trace = null;

        for ($i=count($traces)-1; $i>=0; $i--) {
            $trace = $traces[$i];

            if ($trace['file'] === $current_file) {
                $selected_trace = $trace;
            }
        }

        return $selected_trace
            ? readable::path($selected_trace['file']) . '(' . $selected_trace['line'] . ')'
            : "[unknown function]";
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
        $report = class_exists(Thresholds::class)
            ? new Report\Text(
                Thresholds::from(10, 90),
                showUncoveredFiles: false,
                showOnlySummary: !$this->verbose
            )
            : new Report\Text(
                lowUpperBound: 10,
                highLowerBound: 90,
                showUncoveredFiles: false,
                showOnlySummary: !$this->verbose
            );

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
                $result .= (! empty($node["d"]) ? self::COLOR_RED . "+ " . implode("\n", $node["d"]) . "\n" : "") .
                    (! empty($node["i"]) ? self::COLOR_GREEN . "- " . implode("\n", $node["i"]) . "\n" : "");
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
