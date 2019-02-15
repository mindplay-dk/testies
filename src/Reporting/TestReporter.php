<?php

namespace mindplay\testies\Reporting;

use ReflectionClass;
use stdClass;
use TestInterop\AssertionResult;
use TestInterop\TestCase;
use TestInterop\TestListener;
use Throwable;
use function basename;
use function explode;
use function get_class;
use function mindplay\testies\enabled;
use function strpos;
use function trim;

/**
 * This listener prints a report of test-results to console.
 */
class TestReporter implements TestListener, TestCase
{
    const COLOR_RED = "\033[31m";
    const COLOR_GREEN = "\033[32m";
    const COLOR_RESET = "\033[39m";

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var int
     */
    private $current_test = 0;

    /**
     * @var int
     */
    private $last_test = -1;

    /**
     * @var string|null
     */
    private $current_test_name;

    /**
     * @param bool|null $verbose
     */
    public function __construct(?bool $verbose = null)
    {
        $this->verbose = is_bool($verbose)
            ? $verbose
            : enabled("verbose", "v");
    }

    public function beginTestSuite(string $name, array $properties = []): void
    {
        // TODO: Implement beginTestSuite() method.
    }

    public function endTestSuite(): void
    {
        // TODO: Implement endTestSuite() method.
    }

    public function beginTestCase(string $name, ?string $className = null): TestCase
    {
        $this->current_test_name = $name;

        $this->current_test++;

        return $this;
    }

    public function endTestCase(): void
    {
        $this->current_test_name = null;
    }

    public function addResult(AssertionResult $result): void
    {
        if ($this->verbose === false && $result->getResult() === true) {
            //return; // quiet successful assertion
        }

        if ($this->last_test !== $this->current_test) {
            $this->printTitle($this->current_test_name);

            $this->last_test = $this->current_test;
        }

        $detailed = $result->getResult() === false;

        $formatted_value = $this->format($result->getValue(), $detailed);

        $show_diff = $result->hasExpected() && ($result->getValue() !== $result->getExpected());

        if ($show_diff) {
            $formatted_expected = $this->format($result->getExpected(), $detailed);

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
                $output = $result->getValue() === null
                    ? "" // don't display null when there's no difference from the expected value
                    : " ({$formatted_value})";
            }
        }

        $trace = $result->getFile()
            ? " " . basename($result->getFile()) . ":" . $result->getLine()
            : "";

        $message = $result->getMessage() ?: $result->getType();

        $message = $message
            ? " {$message}"
            : "";

        echo ($result->getResult() === true ? "PASS" : "FAIL") . $trace . $message . $output . "\n";
    }

    public function addError(Throwable $error): void
    {
        echo __METHOD__ . $error->getMessage() . "\n" . $error->getTraceAsString();
        die();
        // TODO: Implement addError() method.
    }

    public function setSkipped(string $reason): void
    {
        echo __METHOD__ . "\n" . $reason;
        die();
        // TODO: Implement setSkipped() method.
    }

    /**
     * Indents multi-line text for display.
     *
     * @param string $str
     *
     * @return string
     */
    private function indent(string $str): string
    {
        return "  " . implode("\n  ", explode("\n", trim($str))) . "\n";
    }

    /**
     * Format a value for display (for use in diagnostic messages)
     *
     * @param mixed $value    the value to format for display
     * @param bool  $detailed true to format the value with more detail
     *
     * @return string formatted value
     */
    private function format($value, bool $detailed = false): string
    {
        // TODO formatter abstraction

        return $this->formatValue($value, "", $detailed);
    }

    /**
     * @param mixed  $value    the value to format for display
     * @param string $indent   indentation string
     * @param bool   $detailed true to format the value with more detail
     *
     * @return string
     */
    private function formatValue($value, string $indent, bool $detailed): string
    {
        $type = is_array($value) && is_callable($value)
            ? "callable"
            : strtolower(gettype($value));

        switch ($type) {
            case "boolean":
                return $value ? "TRUE" : "FALSE";

            case "integer":
                return number_format($value, 0, "", "");

            case "double":
                $formatted = sprintf("%.6g", $value);

                return $value == $formatted
                    ? "{$formatted}"
                    : "~{$formatted}";

            case "string":
                return '"' . strtr($value, ["\r" => '\\r', '"' => '\\"']) . '"';

            case "array":
                return $detailed
                    ? "[" . $this->formatArrayValues($value, "{$indent}  ") . "{$indent}]"
                    : "array[" . count($value) . "]";

            case "object":
                if ($value instanceof Throwable) {
                    $details = $value->getMessage();

                    if ($detailed) {
                        $details .= "\n\nStacktrace:\n" . $value->getTraceAsString();
                    }

                    return get_class($value) . ":\n{$details}";
                }

                return $detailed
                    ? $this->formatObject($value, $indent)
                    : "{" . get_class($value) . "}";

            case "resource":
            case "resource (closed)":
                return "{" . get_resource_type($value) . "}";

            case "callable":
                return is_object($value[0])
                    ? "{" . get_class($value[0]) . "}->{$value[1]}()"
                    : "{$value[0]}::{$value[1]}()";

            case "null":
                return "null";
        }

        return "{{$type}}"; // unsupported types (including "unknown type")
    }

    private function formatObject($object, string $indent): string
    {
        static $stack = [];

        if (in_array($object, $stack, true)) {
            return "{" . get_class($object) . "}";
        }

        $stack[] = $object;

        $type = $object instanceof stdClass ? "" : get_class($object);

        $formatted = "{" . $type . $this->formatProperties($object, "{$indent}  ") . "{$indent}}";

        array_pop($stack);

        return $formatted;
    }

    private function formatProperties($object, string $indent = ""): string
    {
        $values = $this->getObjectProperties($object);

        $formatted = [];

        foreach ($values as $name => $value) {
            $formatted[] = "\n{$indent}\${$name} = " . $this->formatValue($value, $indent, true);
        }

        return implode(",", $formatted) . (count($formatted) ? "\n" : "");
    }

    private function formatArrayValues(array $values, string $indent = ""): string
    {
        $formatted = [];

        foreach ($values as $name => $value) {
            $name = is_int($name)
                ? "{$name}"
                : "\"{$name}\"";

            $formatted[] = "\n{$indent}{$name} => " . $this->formatValue($value, $indent, true);
        }

        return implode(",", $formatted) . (count($formatted) ? "\n" : "");
    }

    private function getObjectProperties($object): array
    {
        if ($object instanceof stdClass) {
            return (array) $object;
        }

        $reflection = new ReflectionClass($object);

        $props = [];

        foreach ($reflection->getProperties() as $prop) {
            $prop->setAccessible(true);

            $props[$prop->getName()] = $prop->getValue($object);
        }

        return $props;
    }

    /**
     * Print the title of the test being executed
     *
     * @param string $title
     *
     * @return void
     */
    private function printTitle(string $title)
    {
        echo "\n=== $title ===\n\n";
    }

    /**
     * Print summary results after completing a test run
     */
    private function printSummary()
    {
        // TODO move to console report listener
        $tests = count($this->tests);

        echo "\n* {$tests} tests completed: {$this->assertions} assertions, {$this->failures} failures\n";
    }

    /**
     * Renders a color-coded, line-by-line diff of two given multi-line strings.
     *
     * @param string $old
     * @param string $new
     *
     * @return string
     */
    private function formatDiff(string $old, string $new): string
    {
        // TODO consider abstracting the diff facility for reuse?

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
