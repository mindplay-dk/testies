<?php

namespace mindplay\testies;

use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;

/**
 * This class creates and configures the test-driver.
 *
 * It's exposed via the {@see configure()} function.
 */
class TestConfiguration
{
    /**
     * @var TestDriver test-driver
     */
    public $driver;

    /**
     * @param TestDriver $driver optional test-driver
     */
    public function __construct(TestDriver $driver = null)
    {
        // TODO move to console report listener
        if (enabled("verbose", "v")) {
            $this->enableVerboseOutput();
        }
    }

    /**
     * Enables code coverage reporting for the test-suite (if available.)
     *
     * Requires the php-unit code coverage package to be installed.
     *
     * @link https://packagist.org/packages/phpunit/php-code-coverage
     *
     * @param string          $output_path       absolute path to code coverage (clover.xml) file
     *                                           example: __DIR__ . '/build/logs/clover.xml'
     * @param string|string[] $source_paths      one or more paths to source folders (of code being tested)
     *                                           example: dirname(__DIR__) . '/src'
     *
     * @return $this
     */
    public function enableCodeCoverage(string $output_path = null, $source_paths = [])
    {
        // TODO move to coverage listener

        if (class_exists(CodeCoverage::class)) {
            try {
                $coverage = new CodeCoverage();

                $filter = $coverage->filter();

                foreach ((array)$source_paths as $path) {
                    $filter->addDirectoryToWhitelist($path);
                }

                $this->driver->coverage = $coverage;
                $this->driver->coverage_output_path = $output_path;
            } catch (RuntimeException $e) {
                echo "# Notice: {$e->getMessage()}\n";
            }
        } else {
            echo "# Notice: php-code-coverage not installed\n";
        }

        return $this;
    }

}
