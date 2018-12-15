<?php

namespace mindplay\testies;

use Closure;

/**
 * This model represents the individual Test, essentially a function
 * with a human-readable name.
 */
class Test
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Closure
     */
    private $function;

    /**
     * @param string  $name     logical name of the Test
     * @param Closure $function function that performs the test
     *
     * @internal
     *
     * @see TestSuite::add()
     */
    public function __construct(string $name, Closure $function)
    {
        $this->name = $name;
        $this->function = $function;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFunction(): Closure
    {
        return $this->function;
    }
}
