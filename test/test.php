<?php

use GuzzleHttp\Client;
use mindplay\testies\TestServer;

require dirname(__DIR__) . '/vendor/autoload.php';

configure()->enableVerboseOutput();
#configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

test(
    'Can run a local test-server',
    function () {
        $server = new TestServer(__DIR__, 8088);

        $client = new Client();

        $response = $client->get('http://127.0.0.1:8088/server.php');

        eq($response->getStatusCode(), 200);
        ok($response->getBody() == 'it works!');
    }
);

exit(run()); // exits with errorlevel (for CI tools etc.)
