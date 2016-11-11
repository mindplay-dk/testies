<?php

use GuzzleHttp\Client;
use mindplay\testies\TestServer;

require dirname(__DIR__) . '/vendor/autoload.php';

configure()->enableVerboseOutput();
configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

$server = new TestServer(__DIR__, 8088);

test(
    'Can run a local test-server',
    function () {
        $client = new Client();

        $response = $client->get('http://127.0.0.1:8088/server.php');

        eq($response->getStatusCode(), 200, 'it should return a 200 status code');
        ok($response->getBody() == 'it works!', 'it should return the script output');
    }
);

exit(run()); // exits with errorlevel (for CI tools etc.)
