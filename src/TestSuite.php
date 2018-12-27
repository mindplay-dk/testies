<?php

namespace mindplay\testies;

use Closure;

/**
 * This model represents a self-contained suite of Tests, often
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
     * @var Test[]
     */
    private $tests = [];

    /**
     * @param string $name       logical name of the test-suite.
     * @param array  $properties optional properties, such as environment variables
     *                           or test-suite configuration settings.
     */
    public function __construct(string $name, array $properties = [])
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * Add a Test to this Suite.
     *
     * @param string  $name     logical name of the Test
     * @param Closure $function function that performs the test
     */
    public function add(string $name, Closure $function)
    {
        $this->tests[] = new Test($name, $function);
    }

    /**
     * @return Test[]
     */
    public function getTests(): array
    {
        return $this->tests;
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
