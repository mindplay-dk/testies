<?php

use mindplay\testies\TestServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Zaphyr\HttpClient\Client;

use function mindplay\testies\{configure, eq, ok, run, test};

require_once dirname(__DIR__) . "/vendor/autoload.php";

configure()->enableCodeCoverage(__DIR__ . "/build/clover.xml", dirname(__DIR__) . "/src");

function clean_report(string $str): string
{
    $str = preg_replace('/[\r\n]/', "\n", $str);                                     // normalize line-endings
    $str = preg_replace('/(Code Coverage Report:.*\n).*?(\n)/', '${1}DATE$2', $str); // remove date/time
    $str = preg_replace('/\s+$/m', '', $str);                                        // clean trailing white space
    return $str;
}

test(
    "Check test result",
    function () {
        $actual_output_path = __DIR__ . "/build/actual-output.txt";
        $actual_output = clean_report(file_get_contents($actual_output_path));

        $expected_output_path = __DIR__ . "/expected-output.txt";
        $expected_output = clean_report(file_get_contents($expected_output_path));

        eq(
            $actual_output,
            $expected_output,
            "should produce test-output as dictated in \"expected-output.txt\""
        );
    }
);

test(
    "Can run a local test-server",
    function () {
        $server = new TestServer(__DIR__, 8088);

        $http = new Psr17Factory();
        $client = new Client($http, $http);

        $response = $client->sendRequest($http->createRequest("GET", "http://127.0.0.1:8088/server.php"));

        eq($response->getStatusCode(), 200, "it should return a 200 status code");
        ok($response->getBody() == "it works!", "it should return the script output");

        unset($server);
    }
);

exit(run()); // exits with errorlevel (for CI tools etc.)
