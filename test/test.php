<?php

use function mindplay\testies\enabled;
use mindplay\testies\Reporting\TestReporter;
use mindplay\testies\Tester;
use mindplay\testies\TestRunner;
use mindplay\testies\TestSuite;

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
    "Can measure Test Result",
    function (Tester $is) {
        $suite = new TestSuite("Mock Test");

        $runner = new TestRunner();

        $is->eq($runner->run($suite, []), true, "an empty Test Suite passes by default");

        $suite->add(
            "Successful Case",
            function (Tester $is) {
                $is->ok(true);
            }
        );

        $is->eq($runner->run($suite, []), true, "succesful assertions generate a passing test");

        $suite->add(
            "Fail Case",
            function (Tester $is) {
                $is->ok(false);
            }
        );

        $is->eq($runner->run($suite, []), false, "failed assertions generate a failing test");

        $bad_suite = new TestSuite("Mock Test");

        $bad_suite->add(
            "Fail Case",
            function () {
                throw new Exception();
            }
        );

        $is->eq($runner->run($bad_suite, []), false, "unexpected error generates a failing test");
    }
);

$suite->add("Run test-suite and emit Test Report", function (Tester $is) {
    exec("php mock_test.php", $output, $status);

    $output = implode("\n", $output);

    $is->eq($status, 0);

    $is->eq($output, file_get_contents(__DIR__ . "/expected-output.txt"));
});

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

if (enabled("mock-only")) {
    exec("php mock_test.php", $output, $status);

    if ($status === 0) {
        $output = implode("\n", $output);

        echo "Updating expected output:\n\n{$output}\n";

        file_put_contents(__DIR__ . "/expected-output.txt", $output);
    }

    exit($status);
}

exit($runner->run($suite, [new TestReporter()]) ? 0 : 1); // exits with errorlevel (for CI tools etc.)
