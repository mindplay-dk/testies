<?php

namespace mindplay\testies;

use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;

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
        $this->driver = $driver ?: $this->createDefaultDriver();

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
        if (class_exists(CodeCoverage::class)) {
            try {
                $filter = new Filter();

                $filter->includeFiles($this->findSourceFiles((array)$source_paths));
                
                $selector = new Selector();
                $driver = $selector->forLineCoverage($filter);
                $coverage = new CodeCoverage($driver, $filter);

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

    /**
     * @param string[] list of source folder paths
     * 
     * @return string[] list of PHP file paths
     */
    private function findSourceFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            $files = array_merge(
                $files,
                array_filter(glob("{$path}/*.php"), "is_file"),
                $this->findSourceFiles(glob("{$path}/*", GLOB_ONLYDIR))
            );
        }

        return $files;
    }

    /**
     * Disables built-in strict error handling.
     *
     * @return $this
     */
    public function disableErrorHandler()
    {
        $this->driver->strict = false;

        return $this;
    }

    /**
     * Enable throwing of unexpected exceptions in tests (useful for debugging)
     *
     * @return $this
     */
    public function throwExceptions()
    {
        $this->driver->throw = true;

        return $this;
    }

    /**
     * Enables verbose output (outputs every successful assertion.)
     *
     * @return $this
     */
    public function enableVerboseOutput()
    {
        $this->driver->verbose = true;

        return $this;
    }

    /**
     * Creates the default test-driver (when none is given via constructor)
     *
     * @return TestDriver default test-driver
     */
    protected function createDefaultDriver()
    {
        return new TestDriver();
    }
}
