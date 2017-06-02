<?php

namespace mindplay\testies;

use Closure;

/**
 * Internal model for individual tests.
 */
class Test
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var Closure
     */
    private $function;

    /**
     * @param string  $title
     * @param Closure $function
     */
    public function __construct($title, Closure $function)
    {
        $this->title = $title;
        $this->function = $function;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return Closure
     */
    public function getFunction()
    {
        return $this->function;
    }
}
