<?php

use GuzzleHttp\Psr7\Message;
use PandaLeague\MockServer\Storage\Doctrine\DoctrineStorage;

require __DIR__.'/../vendor/autoload.php';

// Storage that we can use cross processes
$storage = new DoctrineStorage(['url' => 'sqlite:///somedb.sqlite']);

// HTTP Mock Server
$httpMock = new \PandaLeague\MockServer\HttpMock(
    '127.0.0.1',
    '34457',
    \Ratchet\Http\HttpServer::class,
    $storage
);

// Set some expectations on the HTTP server:
// If we send any PUT request, return this specific response
$response = new \GuzzleHttp\Psr7\Response(200, ['X-My-header' => 'test-header'], 'Method matches');
$httpMock->expectsHttp('PUT', $response);

// Set some expectations on the HTTP server:
// The request must be matched exactly
$request1 = new \GuzzleHttp\Psr7\Request('POST', '/my-post-url', ['X-Required' => 'musthave'], 'some  required data');
$response = new \GuzzleHttp\Psr7\Response(200, ['X-My-header' => 'test-header'], 'URL, Headers and body matches');
$httpMock->expectsHttp('POST', $response, $request1);

// Set some expectations on the HTTP server:
// The request must be matched exactly - empty body will match all
$request2 = new \GuzzleHttp\Psr7\Request('POST', '/my-post-url', ['X-Required' => 'must have'], '');
$response = new \GuzzleHttp\Psr7\Response(200, ['X-My-header' => 'test-header'], 'URL && headers match');
$httpMock->expectsHttp('POST', $response, $request2);

// Set some expectations on the HTTP server:
// The request must be matched exactly - empty body will match all, empty headers will match all
$request3 = new \GuzzleHttp\Psr7\Request('POST', '/my-post-url', [], '');
$response = new \GuzzleHttp\Psr7\Response(200, ['X-My-header' => 'test-header'], 'URL Match');
$httpMock->expectsHttp('POST', $response, $request3, 1);

// Start the Stomp Server (expectations can still be added after the start)
$httpMock->start();
$httpMock->waitUntil(function ($type, $output) {
    echo $output;
    return strpos($output, 'Server Started') === 0;
});


try {
    $client = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:34457', 'http_errors'=>false]);
    echo "Test1:\n-------\n";
    $response = $client->get('/500');
    echo Message::toString($response)."\n---------\n";

    echo "Test2:\n-------\n";
    $request = new \GuzzleHttp\Psr7\Request('PUT', '/anything');
    $response = $client->send($request, ['timeout' => 2]);
    echo Message::toString($response)."\n---------\n";

    echo "Test3:\n-------\n";
    $response = $client->send($request1, ['timeout' => 2]);
    echo Message::toString($response)."\n---------\n";

    echo "Test4:\n-------\n";
    $response = $client->send($request2, ['timeout' => 2]);
    echo Message::toString($response)."\n---------\n";

    echo "Test5:\n-------\n";
    $response = $client->send($request3, ['timeout' => 3]);
    echo Message::toString($response)."\n---------\n";

    echo "Test5.1:\n-------\n";
    $response = $client->send($request3, ['timeout' => 3]);
    // We should get a 404 here because we've set it in the expectation that this response
    // can only be returned once
    echo Message::toString($response)."\n---------\n";

    // This will throw a timeout exception
    //$response = $client->get('/418?_mock_delay=3', ['timeout' => 2]);

    $stack = $storage->loadStack($httpMock->getServerId());
    echo '--------- Call Stack ---------'."\n";
    print_r($stack);
} catch (\Throwable $e) {
    echo $e->getMessage();
} finally {
    echo $httpMock->getOutput();
    $httpMock->stop();
}
