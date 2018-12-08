<?php

namespace mindplay\testies;

use Closure;

/**
 * This model represents a self-contained suite of Test Cases, often
 * described as either "unit", "integration" or "functional", etc.
 *
 * @see TestRunner::run()
 */
class TestSuite
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var TestCase[]
     */
    private $test_cases = [];

    /**
     * @param string $name       logical name of the test-suite.
     * @param array  $properties optional properties, such as environment variables
     *                           or test-suite configuration settings.
     */
    public function __construct(string $name, array $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * Add a Test Case to the Test Suite.
     *
     * @param string  $name     logical name of the Test Case
     * @param Closure $function function that performs the test
     */
    public function add(string $name, Closure $function)
    {
        $this->test_cases[] = new TestCase($name, $function);
    }

    /**
     * @return TestCase[]
     */
    public function getTestCases(): array
    {
        return $this->test_cases;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }
}
