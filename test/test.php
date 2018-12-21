<?php

use mindplay\testies\Recording\TestRecorder;
use mindplay\testies\Tester;
use mindplay\testies\TestRunner;
use mindplay\testies\TestSuite;
use function mindplay\testies\{inspect, invoke};

require dirname(__DIR__) . "/vendor/autoload.php";

class Foo
{
    private $bar = "blip";

    protected function blip()
    {
        return $this->bar;
    }
}

// TODO test for code coverage

$suite = new TestSuite("Integration Test");

$suite->add(
    "Can run Test Suite",
    function (Tester $is) {
        $suite = new TestSuite("Mock Test");

        $suite->add(
            "Hello World",
            function (Tester $is) {
                $is->ok(true);
                $is->ok(false);

                $is->ok(true, "why");
                $is->ok(false, "why");

                $is->ok(true, "why", "string");
                $is->ok(false, "why", "line 1\nline 2");

                $is->eq("string", "string"); // equal strings

                // multi-line strings:

                $is->eq("line 1\nline 2\nline 3", "line 1\nline 2\nline 3"); // equal
                $is->eq("line 1\nline 2\nline 3", "line 1\nline 3\nline 4"); // not equal

                $is->eq("foo", "foo", "why");
                $is->eq("foo", "bar", "why");

//                $is->eq(format([1, 2, 3]), "array[3]");
//                $is->eq(format(true), "TRUE");
//                $is->eq(format(false), "FALSE");
//                $is->eq(format(new Foo), "Foo");

                $is->eq(invoke(new Foo, "blip"), "blip");

                $is->eq(inspect(new Foo, "bar"), "blip");

//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("boom"); // succeeds
//                    }
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("booooooom");
//                    },
//                    "/bo+m/" // succeeds
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        throw new RuntimeException("bam");
//                    },
//                    "/bo+m/" // fails
//                );
//
//                expect(
//                    RuntimeException::class,
//                    "why",
//                    function () {
//                        // doesn't throw
//                    }
//                );

                throw new RuntimeException("THE END");
            }
        );

        $runner = new TestRunner();

        $recorder = new TestRecorder();

        $is->eq($runner->run($suite, [$recorder]), false, "the test should fail");

        $is->eq(count($recorder->getSuites()), 1);

        // TODO test recorded results!
    }
);

//test(
//    "Can run a local test-server",
//    function () {
//        $server = new TestServer(__DIR__, 8088);
//
//        $client = new Client();
//
//        $response = $client->get("http://127.0.0.1:8088/server.php");
//
//        eq($response->getStatusCode(), 200, "it should return a 200 status code");
//        ok($response->getBody() == "it works!", "it should return the script output");
//
//        unset($server);
//    }
//);

$runner = new TestRunner();

exit($runner->run($suite, []) ? 0 : 1); // exits with errorlevel (for CI tools etc.)
