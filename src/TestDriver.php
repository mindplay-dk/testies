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
}
