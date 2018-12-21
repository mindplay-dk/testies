<?php

namespace mindplay\testies\Recording;

use TestInterop\AssertionResult;
use TestInterop\TestCase;
use Throwable;

class RecordedTestCase implements TestCase
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $class_name;

    /**
     * @var AssertionResult[]
     */
    private $results = [];

    /**
     * @var Throwable[]
     */
    private $errors;

    /**
     * @var string|null
     */
    private $reason_skipped;

    /**
     * @var string|null
     */
    private $reason_disabled;

    public function __construct($name, $class_name)
    {
        $this->name = $name;
        $this->class_name = $class_name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClassName(): string
    {
        return $this->class_name;
    }

    public function addResult(AssertionResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return AssertionResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function addError(Throwable $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * @return Throwable[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setSkipped(string $reason): void
    {
        $this->reason_skipped = $reason;
    }

    public function getReasonSkipped(): ?string
    {
        return $this->reason_skipped;
    }

    public function setDisabled(string $reason): void
    {
        $this->reason_disabled = $reason;
    }

    public function getReasonDisabled(): ?string
    {
        return $this->reason_disabled;
    }
}
