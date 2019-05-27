<?php

use function mindplay\testies\enabled;
use mindplay\testies\Reporting\TestReporter;
use mindplay\testies\Tester;
use mindplay\testies\TestRunner;
use mindplay\testies\TestSuite;

require dirname(__DIR__) . "/vendor/autoload.php";

// TODO test for code coverage

function truncate_paths(string $output): string
{
    $cwd = getcwd();

    $dir = $cwd;

    while (! file_exists("{$dir}/composer.json")) {
        $parent_dir = dirname($dir);

        if ($parent_dir === $dir) {
            throw new RuntimeException("Unable to locate Composer root from: {$cwd}");
        }

        $dir = $parent_dir;
    }

    return str_replace($dir, "{root}", $output);
}

function run_mock_test(): string
{
    exec("php " . __DIR__ . "/mock_test.php", $output, $status);

    return "exit code: {$status}\n\n" . truncate_paths(implode("\n", $output));
}

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
    $output = run_mock_test();

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
    $output = run_mock_test();

    echo "Updating expected output:\n\n{$output}\n";

    file_put_contents(__DIR__ . "/expected-output.txt", $output);

    exit(0);
}

exit($runner->run($suite, [new TestReporter()]) ? 0 : 1); // exits with errorlevel (for CI tools etc.)
