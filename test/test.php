<?php

use GuzzleHttp\Client;
use mindplay\testies\TestServer;
use function mindplay\testies\{configure, test, ok, eq, run};

require dirname(__DIR__) . '/vendor/autoload.php';

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

test(
    'Can run a local test-server',
    function () {
        $server = new TestServer(__DIR__, 8088);

        $client = new Client();

        $response = $client->get('http://127.0.0.1:8088/server.php');

        eq($response->getStatusCode(), 200, 'it should return a 200 status code');
        ok($response->getBody() == 'it works!', 'it should return the script output');

        unset($server);
    }
);

exit(run()); // exits with errorlevel (for CI tools etc.)
