<?php

use GuzzleHttp\Client;
use mindplay\testies\TestConfiguration;
use mindplay\testies\TestServer;
use function mindplay\testies\{configure, test, ok, eq, expect, run, format, invoke, inspect};

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

        ok(true, "T");
        ok(false, "F");

        ok(true, "T", "baz");
        ok(false, "F", "bat\nbit");

        eq("a", "a");
        eq("a", "a\nb\nc");
        eq("a", "a\nb\nc", "because");

        eq("a", "a", "T");
        eq("a", "b", "F");

        eq(format([1,2,3]), "array[3]");
        eq(format(true), "TRUE");
        eq(format(false), "FALSE");
        eq(format(new Foo), "Foo");

        eq(invoke(new Foo, "blip"), "blip");

        eq(inspect(new Foo, "bar"), "blip");

        expect(
            RuntimeException::class,
            "T",
            function () {
                throw new RuntimeException("boom"); // succeeds
            }
        );

        expect(
            RuntimeException::class,
            "T",
            function () {
                throw new RuntimeException("booooooom");
            },
            "/bo+m/" // succeeds
        );

        expect(
            RuntimeException::class,
            "F",
            function () {
                throw new RuntimeException("bam");
            },
            "/bo+m/" // fails
        );

        expect(
            RuntimeException::class,
            "F",
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

//echo $result; exit;

configure(new TestConfiguration());

configure()->enableVerboseOutput();

configure()->enableCodeCoverage(__DIR__ . "/build/clover.xml", dirname(__DIR__) . "/src");

test(
    "Check test result",
    function () use ($result) {
        eq(trim($result), trim(str_replace("\r\n", "\n", file_get_contents(__DIR__ . "/expected-output.txt"))),
            "should produce test-output as dictated in \"expected-output.txt\"");
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
