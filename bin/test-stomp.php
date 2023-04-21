<?php

use PandaLeague\MockServer\Server\Stomp\StompFrames;
use PandaLeague\MockServer\Server\Stomp\StompServer;
use PandaLeague\MockServer\ServerMock;
use PandaLeague\MockServer\Storage\Doctrine\DoctrineStorage;

// The package php-stomp/php-stomp is throwing Deprecated errors. No upgrade path yet
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__.'/../vendor/autoload.php';

// Storage that we can use cross processes
$storage = new DoctrineStorage(['url' => 'sqlite:///somedb.sqlite']);

// Stomp Mock Server
$stompMock = new \PandaLeague\MockServer\StompMock(
    '127.0.0.1',
    '34456',
    StompServer::class,
    $storage
);

// Set some expectations for when we connect to the STOMP server
$stompFrames = new StompFrames(1);
//$stompMock->expectsStomp(
//    'CONNECT',
//    $stompFrames->getNotAuthedErrorFrame(),
//    null,
//    1
//);

$stompMock->expectsStomp(
    'SUBSCRIBE',
    $stompFrames->getMessageFrame(
        \Ramsey\Uuid\Uuid::uuid4()->toString(),
        '/queue/my-test-queue',
        ['some' => 'thing']
    )
);

// Start the Stomp Server (expectations can still be added after the start)
$stompMock->start();
$stompMock->waitUntil(function ($type, $output) {
    echo $output;
    return strpos($output, 'Server Started') === 0;
});

try {
    $stompClient = new \Stomp\Client('tcp://127.0.0.1:34456');
    $stompClient->connect();
    $stomp = new \Stomp\SimpleStomp($stompClient);
    $stomp->subscribe('/queue/my-test-queue', null, 'client-individual');
    $message = $stomp->read();
    $stomp->ack($message);
    print_r($message);
    $stomp->send('my-queue', new \Stomp\Transport\Message(str_repeat('*', 15000).'1'));
} catch (\Throwable $e) {
    echo $e->getMessage();
} finally {
    echo $stompMock->getOutput();
    $stompMock->stop();
}
