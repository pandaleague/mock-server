<?php

declare(strict_types=1);

namespace PandaLeague\MockServer\Server\Stomp;

use PandaLeague\MockServer\Storage\StorageAware;
use PandaLeague\MockServer\Storage\StorageAwareTrait;
use Ratchet\ConnectionInterface;
use Stomp\Transport\Frame;
use Stomp\Transport\Parser;

class StompMockManager implements StompInterface, StorageAware
{
    use StorageAwareTrait;

    /** @var string */
    private $serverId;

    /** @var StompFrames */
    private $frames;

    /** @var StompManager */
    private $server;

    /** @var Parser */
    private $parser;

    public function __construct(StompManager $server)
    {
        $this->parser = new Parser();
        $this->server = $server;
        $this->serverId = $server->getServerId();
    }

    public function onConnect(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onConnect($conn, $frame);
        }
    }

    public function onSend(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onSend($conn, $frame);
        }
    }

    public function onSubscribe(ConnectionInterface $conn, Frame $frame)
    {
        $this->server->onSubscribe($conn, $frame);
        $this->sendExpectations($conn, $frame);
    }

    public function onUnSubscribe(ConnectionInterface $conn, Frame $frame)
    {
        $this->server->onUnSubscribe($conn, $frame);
        $this->sendExpectations($conn, $frame);
    }

    public function onBegin(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onBegin($conn, $frame);
        }
    }

    public function onCommit(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onCommit($conn, $frame);
        }
    }

    public function onAbort(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onAbort($conn, $frame);
        }
    }

    public function onAck(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onAck($conn, $frame);
        }
    }

    public function onNack(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onNack($conn, $frame);
        }
    }

    public function onDisconnect(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onDisconnect($conn, $frame);
        }
    }

    public function onHeartBeat(ConnectionInterface $conn, Frame $frame)
    {
        if ($this->sendExpectations($conn, $frame) === null) {
            $this->server->onHeartBeat($conn, $frame);
        }
    }

    public function sendExpectations(ConnectionInterface $conn, Frame $frame): ?Frame
    {
        $expectation = $this->getMatchedExpectation($frame);

        if (is_null($expectation)) {
            return null;
        }

        $this->parser->addData($expectation['response']);
        $response = $this->parser->nextFrame();
        $this->getStorage()->expectationMatched((int)$expectation['id']);
        $this->getStorage()->pushToStack(
            $this->serverId,
            $frame->getCommand(),
            $frame->__toString(),
            $response->__toString(),
            (int) $expectation['id']
        );

        $conn->send($response);

        return $response;
    }

    public function getMatchedExpectation(Frame $frame): ?array
    {
        $expectations = $this->getStorage()->loadExpectations(
            $this->serverId,
            $frame->getCommand(),
            true
        );

        $frameHeaders = $frame->getHeaders();

        foreach ($expectations as $expectation) {
            if ($expectation['request'] == '') {
                $expectation['response'] = $this->replacePlaceholders($expectation['response'], $frameHeaders);

                return $expectation;
            }

            $this->parser->addData($expectation['request']);
            $request = $this->parser->nextFrame();

            $match = true;
            foreach ($request->getHeaders() as $header => $value) {
                if (!isset($frameHeaders[$header]) || $frameHeaders[$header] != $value) {
                    $match = false;
                    break;
                }
            }

            if ($match && !empty($request->getBody()) && $request->getBody() != $frame->getBody()) {
                $match = false;
            }

            if ($match) {
                $expectation['response'] = $this->replacePlaceholders($expectation['response'], $frameHeaders);

                return $expectation;
            }
        }

        return null;
    }

    private function replacePlaceholders($response, $frameHeaders): string
    {
        foreach ($frameHeaders as $header => $value) {
            $response = str_replace('%' . $header . '%', $value, $response);
        }

        return $response;
    }
}
