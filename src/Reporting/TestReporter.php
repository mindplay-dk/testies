<?php

namespace mindplay\testies\Reporting;

use function addcslashes;
use function count;
use function implode;
use RuntimeException;
use function strlen;
use TestInterop\AssertionResult;
use TestInterop\TestCase;
use TestInterop\TestListener;
use Throwable;
use function basename;
use function explode;
use function get_class;
use function mindplay\testies\enabled;
use function print_r;
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
     * @var bool
     */
    private $short_paths;

    /**
     * @var string|null
     */
    private $base_path = null;

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
     * @param bool $verbose     enables verbose output (details of all assertions, even successful ones)
     * @param bool $short_paths truncates file-system paths in output (more readable, less machine-friendly)
     */
    public function __construct(bool $verbose = false, bool $short_paths = false)
    {
        $this->verbose = $verbose;
        $this->short_paths = $short_paths;

        if ($short_paths) {
            $cwd = getcwd();

            $dir = $cwd;

            while (! file_exists("{$dir}/composer.json")) {
                $parent_dir = dirname($dir);

                if ($parent_dir === $dir) {
                    throw new RuntimeException("Unable to locate Composer root from: {$cwd}");
                }

                $dir = $parent_dir;
            }

            $this->base_path = $this->normalizePath($dir) . "/";
        }
    }

    public function beginTestSuite(string $name, array $properties = []): void
    {
        // TODO: Implement beginTestSuite() method.
    }

    public function endTestSuite(): void
    {
        // TODO $this->printSummary();
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
            return; // quiet successful assertion
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

        $path = $result->getFile();

        $trace = "";

        if ($path) {
            $path = $this->short_paths
                ? basename($path)
                : $this->normalizePath($path);

            $line = $result->getLine();

            $trace = " {$path}({$line})";
        }

        $message = $result->getMessage() ?: $result->getType();

        $message = $message
            ? " {$message}"
            : "";

        echo ($result->getResult() === true ? "PASS" : "FAIL") . $trace . $message . $output . "\n";
    }

    public function addError(Throwable $error): void
    {
        echo "ERROR:\n" . $this->indent($this->format($error, true)) . "\n";
    }

    public function setSkipped(string $reason): void
    {
        echo "SKIPPED: {$reason}\n";
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

        if ($value instanceof Throwable) {
            if ($detailed) {
                return $this->formatThrowable($value);
            }

            return get_class($value);
        }

        if (is_string($value) && (strpos($value, "\n") === false)) {
            return "'" . addcslashes($value, "'") . "'";
        }

        if (! $detailed && is_array($value)) {
            return 'array[' . count($value) . ']';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_object($value) && ! $detailed) {
            return "{" . get_class($value) . "}";
        }

        if (is_null($value)) {
            return "null";
        }

        return str_replace("\r\n", "\n", print_r($value, true));
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

        $added = self::COLOR_GREEN . "+ ";
        $removed = self::COLOR_RED . "- ";

        foreach ($diff as $node) {
            if (is_array($node)) {
                $result .= (! empty($node["d"]) ? $removed . implode("\n{$removed}", $node["d"]) . "\n" : "") .
                    (! empty($node["i"]) ? $added . implode("\n{$added}", $node["i"]) . "\n" : "");
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

    /**
     * Render a human-readable error message and stack-trace.
     *
     * @param Throwable $error
     *
     * @return string
     */
    private function formatThrowable(Throwable $error): string
    {
        $trace = $error->getTrace();

        $formatted = [];

        foreach ($trace as $index => $entry) {
            $line = array_key_exists("line", $entry)
                ? $entry["line"]
                : "";

            $file = isset($entry["file"])
                ? $this->truncatePath($this->normalizePath($entry["file"]))
                : "{internal function}";

            $function = isset($entry["class"])
                ? $entry["class"] . @$entry["type"] . @$entry["function"]
                : @$entry["function"];

            if ($function === "require" || $function === "include") {
                // bypass argument formatting for include and require statements
                $args = isset($entry["args"]) && is_array($entry["args"])
                    ? reset($entry["args"])
                    : "";
            } else {
                $args = isset($entry["args"]) && is_array($entry["args"])
                    ? implode(
                        ", ",
                        array_map(
                            function ($value) {
                                return $this->format($value, false);
                            },
                            $entry["args"]
                        )
                    )
                    : "";
            }

            $call = $function
                ? "{$function}({$args})"
                : "";

            $depth = $index + 1;

            $formatted[] = sprintf("%4s", "{$depth}.") . " {$file}({$line}): {$call}";
        }

        return get_class($error) . ": "
            . "\"" . $error->getMessage() . "\""
            . "\nin " . $this->truncatePath($this->normalizePath($error->getFile())) . "(" . $error->getLine() . ")"
            . "\n" . implode("\n", $formatted);
    }

    /**
     * Normalizes backslashes (on Windows) to forward slashes for more consistent output.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return strtr($path, "\\", "/");
    }

    /**
     * Truncates file-system paths, if enabled - otherwise, the path is returned as-is.
     *
     * @param string $path
     *
     * @return string
     */
    private function truncatePath(string $path): string
    {
        if ($this->base_path !== null) {
            $offset = strpos($path, $this->base_path);

            if ($offset === 0) {
                return "{root}/" . substr($path, strlen($this->base_path));
            }
        }

        return $path;
    }
}
