<?php

namespace mindplay\testies\Reporting;

use function mindplay\testies\enabled;
use TestInterop\TestCase;
use TestInterop\TestListener;

/**
 * This listener prints a report of test-results to console.
 */
class TestReporter implements TestListener
{
    const COLOR_RED = "\033[31m";
    const COLOR_GREEN = "\033[32m";
    const COLOR_RESET = "\033[39m";

    /**
     * @var bool
     */
    private $verbose;

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
        // TODO: Implement beginTestCase() method.
    }

    public function endTestCase(): void
    {
        // TODO: Implement endTestCase() method.
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
