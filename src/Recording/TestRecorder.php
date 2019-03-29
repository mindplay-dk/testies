<?php

namespace mindplay\testies\Recording;

use LogicException;
use TestInterop\TestCase;
use TestInterop\TestListener;

/**
 * Test Listener implementation that silently records every event for later inspection.
 *
 * TODO maybe repurpose this into a test record/playback facility, if that's useful?
 *      otherwise, remove it - the idea was to use this to test integration with
 *      test-listeners, but we have real test-listeners (such as TestReporter) that
 *      fulfill the same purpose, so we don't need it for that...
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
