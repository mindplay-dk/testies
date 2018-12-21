<?php

namespace mindplay\testies\Recording;

use TestInterop\TestCase;

class RecordedTestSuite
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
     * @var RecordedTestCase[]
     */
    private $cases = [];

    public function __construct(string $name, array $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    public function addCase(RecordedTestCase $case)
    {
        $this->cases[] = $case;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return RecordedTestCase[]
     */
    public function getCases(): array
    {
        return $this->cases;
    }
}
