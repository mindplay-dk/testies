<?php

use function mindplay\testies\{configure, eq, expect, format, inspect, invoke, ok, run, test};

require_once dirname(__DIR__) . "/vendor/autoload.php";

configure()->enableVerboseOutput();

configure()->enableCodeCoverage(__DIR__ . "/build/clover.xml", dirname(__DIR__) . "/src");

class Foo
{
    private $bar = "blip";

    protected function blip()
    {
        return $this->bar;
    }
}

test(
    "Hello World",
    function () {
        ok(true);
        ok(false);

        ok(true, "why");
        ok(false, "why");

        ok(true, "why", "string");

        ok(true, "why", "line 1\nline 2");
        ok(false, "why", "line 1\nline 2");

        // single-line strings:

        eq("string", "string");
        eq("string", "string", "why");
        eq("string1", "string2", "why");

        // multi-line strings:

        eq("string", "line 1\nline 2", "why");
        eq("line 1\nline 2", "line 1\nline 2", "why");

        eq("line 1\nline 2\nline 3", "line 1\nline 2\nline 3"); // equal
        eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4"); // not equal
        eq("line 1\nline 2\nline 3", "line 1\nline 2!\nline 3"); // not equal

        // comparing different types:

        eq(123, "123", "why");
        eq("123", 123, "why");

        eq(123, "line 1\nline 2", "why");
        eq("line 1\nline 2", 123, "why");

        eq(invoke(new Foo, "blip"), "blip");

        eq(inspect(new Foo, "bar"), "blip");

        expect(
            RuntimeException::class,
            "why",
            function () {
                throw new RuntimeException("boom"); // succeeds
            }
        );

        expect(
            RuntimeException::class,
            "why",
            function () {
                throw new RuntimeException("booooooom");
            },
            "/bo+m/" // succeeds
        );

        expect(
            RuntimeException::class,
            "why",
            function () {
                throw new RuntimeException("bam");
            },
            "/bo+m/" // fails: unexpected exception message
        );

        expect(
            RuntimeException::class,
            "why",
            function () {
                throw new InvalidArgumentException("bam");
            },
            "/bam/" // fails: wrong exception type
        );

        expect(
            RuntimeException::class,
            "why",
            function () {
                // fails: doesn't throw at all
            }
        );

        throw new RuntimeException("THE END");
    }
);

run();
