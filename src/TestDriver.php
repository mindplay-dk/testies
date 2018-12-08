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
    // TODO move to console report listener

    /**
     * @var CodeCoverage|null active code coverage instance (or NULL if inactive)
     */
    public $coverage;
    // TODO move to coverage listener

    /**
     * @var string absolute path to code coverage report output file (e.g. "clover.xml")
     */
    public $coverage_output_path = null;
    // TODO move to coverage listener

    /**
     * Print the title of the test being executed
     *
     * @param string $title
     *
     * @return void
     */
    public function printTitle(string $title)
    {
        // TODO move to console report listener
        echo "\n=== $title ===\n\n";
    }

    /**
     * Print summary results after completing a test run
     */
    public function printSummary()
    {
        // TODO move to console report listener
        $tests = count($this->tests);

        echo "\n* {$tests} tests completed: {$this->assertions} assertions, {$this->failures} failures\n";
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
        // TODO move to coverage listener
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
        // TODO move to coverage listener
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
        // TODO move to result builder? and/or abstract and inject into Runner
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
