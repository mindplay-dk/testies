<?php

namespace mindplay\testies\Recording;

use LogicException;
use TestInterop\TestCase;
use TestInterop\TestListener;

/**
 * Test Listener implementation that silently records every event for later inspection.
 */
class TestRecorder implements TestListener
{
    /**
     * @var RecordedTestSuite
     */
    private $suite;

    /**
     * @var RecordedTestCase
     */
    private $case;

    /**
     * @var RecordedTestSuite[]
     */
    public $suites = [];

    public function beginTestSuite(string $name, array $properties = []): void
    {
        if ($this->suite !== null) {
            throw new LogicException("Another Test Suite is already active: {$this->suite->getName()}");
        }

        $this->suite = new RecordedTestSuite($name, $properties);
    }

    public function endTestSuite(): void
    {
        if ($this->suite === null) {
            throw new LogicException("No Test Suite is currently active.");
        }

        $this->suites[] = $this->suite;

        $this->suite = null;
    }

    public function beginTestCase(string $name, ?string $class_name = null): TestCase
    {
        if ($this->case !== null) {
            throw new LogicException("Another Test Case is already active: {$this->case->getName()}");
        }

        $this->case = new RecordedTestCase($name, $class_name);

        return $this->case;
    }

    public function endTestCase(): void
    {
        if ($this->case === null) {
            throw new LogicException();
        }

        $this->suite->addCase($this->case);

        $this->case = null;
    }

    /**
     * @return RecordedTestSuite[]
     */
    public function getSuites(): array
    {
        return $this->suites;
    }
}
