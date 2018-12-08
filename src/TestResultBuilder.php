<?php

namespace mindplay\testies;

use TestInterop\Common\AssertionResult;
use Throwable;

/**
 * Builds Assertion Result instances, traces the source of the assertion, etc.
 */
class TestResultBuilder
{
    /**
     * @var TestCase
     */
    private $case;

    public function __construct(TestCase $case)
    {
        $this->case = $case;
    }

    /**
     * Builds an Assertion Result with
     *
     * @param bool        $result   result of assertion (must === TRUE)
     * @param string|null $why      optional description of assertion
     * @param mixed       $value    optional actual value (displays on failure)
     * @param mixed       $expected optional expected value (displays on failure)
     *
     * @return AssertionResult
     */
    public function createResult(bool $result, ?string $why = null, $value = null, $expected = null): AssertionResult
    {
        // TODO move printing/diffing/formatting concerns to console report listener

        if ($this->verbose === false && $result === true) {
            return; // quiet successful assertion
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
     * Obtain a filename and line number index to the call that wasa made in the Test Case function.
     *
     * @return string|null formatted file/line index (or NULL if unable to trace)
     */
    public function trace(): ?string
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

            if (($trace['file'] === __FILE__) && (@$trace['args'][0] === $this->case->getFunction())) {
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
     * Format a value for display (for use in diagnostic messages)
     *
     * @param mixed $value    the value to format for display
     * @param bool  $detailed true to format the value with more detail
     *
     * @return string formatted value
     */
    public function format($value, bool $detailed = false): string
    {
        // TODO formatter abstraction

        if ($value instanceof Throwable) {
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
}
