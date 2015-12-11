<?php

namespace mindplay\testies;

use PHP_CodeCoverage_Exception;
use PHP_CodeCoverage;

/**
 * This class creates and configures the test-driver.
 *
 * It's exposed via the {@link configure()} function.
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
    }

    /**
     * Enables code coverage reporting for the test-suite (if available.)
     *
     * Requires the php-unit code coverage package to be installed.
     *
     * @link https://packagist.org/packages/phpunit/php-code-coverage
     *
     * @param string               $output_path  absolute path to code coverage (clover.xml) file
     *                                           example: __DIR__ . '/build/logs/clover.xml'
     * @param string|string[]|null $source_paths one or more paths to source folders (of code being tested)
     *                                           example: dirname(__DIR__) . '/src'
     *
     * @return $this
     */
    public function enableCodeCoverage($output_path = null, $source_paths = null)
    {
        if (class_exists('PHP_CodeCoverage')) {
            try {
                $coverage = new PHP_CodeCoverage;

                if ($source_paths) {
                    $filter = $coverage->filter();

                    foreach ((array)$source_paths as $path) {
                        $filter->addDirectoryToWhiteList($path);
                    }
                }

                $this->driver->coverage = $coverage;
                $this->driver->coverage_output_path = $output_path;
            } catch (PHP_CodeCoverage_Exception $e) {
                echo "# Notice: no code coverage run-time available\n";
            }
        } else {
            echo "# Notice: php-code-coverage not installed\n";
        }

        return $this;
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
