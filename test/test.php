<?php

use GuzzleHttp\Client;
use mindplay\testies\TestConfiguration;
use mindplay\testies\TestServer;
use function mindplay\testies\{configure, eq, expect, format, inspect, invoke, ok, run, test};

require dirname(__DIR__) . "/vendor/autoload.php";

configure()->enableVerboseOutput();

// TODO test for code-coverage output: configure()->enableCodeCoverage(__DIR__ . "/build/clover.xml", dirname(__DIR__) . "/src");

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
        ok(false, "why", "line 1\nline 2");

        eq("string", "string"); // equal strings

        // multi-line strings:

        eq("line 1\nline 2\nline 3", "line 1\nline 2\nline 3"); // equal
        eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4"); // not equal

        eq("foo", "foo", "why");
        eq("foo", "bar", "why");

        eq(format([1,2,3]), "array[3]");
        eq(format(true), "TRUE");
        eq(format(false), "FALSE");
        eq(format(new Foo), "Foo");

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
            "/bo+m/" // fails
        );

        expect(
            RuntimeException::class,
            "why",
            function () {
                // doesn't throw
            }
        );

        throw new RuntimeException("THE END");
    }
);

ob_start();

run();

$result = ob_get_clean();

// TODO figure out how to test for expected output? it contains a stack-trace, which looks different when xdebug is installed.
//file_put_contents(__DIR__."/expected-output.txt", $result);

configure(new TestConfiguration());

configure()->enableCodeCoverage(__DIR__ . "/build/clover.xml", dirname(__DIR__) . "/src");

test(
    "Check test result",
    function () use ($result) {
        $expected_output_path = __DIR__ . "/expected-output.txt";

        ok(trim($result) === trim(str_replace("\r\n", "\n", file_get_contents($expected_output_path))),
            "should produce test-output as dictated in \"expected-output.txt\"",
            $result);
    }
);

test(
    "Can run a local test-server",
    function () {
        $server = new TestServer(__DIR__, 8088);

        $client = new Client();

        $response = $client->get("http://127.0.0.1:8088/server.php");

        eq($response->getStatusCode(), 200, "it should return a 200 status code");
        ok($response->getBody() == "it works!", "it should return the script output");

        unset($server);
    }
);

exit(run()); // exits with errorlevel (for CI tools etc.)
