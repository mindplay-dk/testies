<?php

namespace mindplay\testies;

use TestInterop\AssertionResult;
use TestInterop\TestCase;
use Throwable;

/**
 * This Test Case implementation internally tracks the net result of the Test.
 *
 * Any failed Assertion or Error will be considered a failed Test.
 */
class TestResult implements TestCase
{
    /**
     * @var bool
     */
    private $has_errors = false;

    public function addResult(AssertionResult $result): void
    {
        if ($result->getResult() === false) {
            $this->has_errors = true;
        }
    }

    public function addError(Throwable $error): void
    {
        $this->has_errors = true;
    }

    public function setSkipped(string $reason): void
    {
        // skipping a test does not affect the result.
    }

    public function setDisabled(string $reason): void
    {
        // disabling a test does not affect the result.
    }

    public function hasErrors(): bool
    {
        return $this->has_errors;
    }
}
