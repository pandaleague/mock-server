<?php

declare(strict_types=1);

namespace PandaLeague\MockServer\Server\Stomp;

use PandaLeague\MockServer\Server\BaseServer;
use PandaLeague\MockServer\Storage\StorageAware;
use PandaLeague\MockServer\Storage\StorageAwareTrait;
use Ratchet\ConnectionInterface;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

class StompManager implements StompInterface, BaseServer, StorageAware
{
    use StorageAwareTrait;

    /** @var string */
    private $serverId;

    /** @var StompFrames */
    private $frames;

    public function __construct(string $serverId)
    {
        $this->serverId = $serverId;
        $this->frames = new StompFrames($serverId, Version::VERSION_1_2, 'ActiveMQ/5.15.4-mock');
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function onConnect(ConnectionInterface $conn, Frame $frame)
    {
        $headers = $frame->getHeaders();
        $heartBeat = [0, 0];
        if (isset($headers['heart-beat'])) {
            $heartBeat = explode(',', $headers['heart-beat']);
        }

        $response = $this->frames->getConnectedFrame($heartBeat, spl_object_id($conn));
        $conn->send($response);
        $this->getStorage()->pushToStack(
            $this->serverId,
            $frame->getCommand(),
            $frame->__toString(),
            $response->__toString()
        );
    }

    public function onSend(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onSubscribe(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onUnSubscribe(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onBegin(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onCommit(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onAbort(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onAck(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onNack(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onDisconnect(ConnectionInterface $conn, Frame $frame)
    {
        $this->sendReceipt($conn, $frame);
    }

    public function onHeartBeat(ConnectionInterface $conn, Frame $frame)
    {
        $response = "\n\n";
        $conn->send($response);
        $this->getStorage()->pushToStack(
            $this->serverId,
            $frame->getCommand(),
            $frame->__toString(),
            $response
        );
    }

    public function sendReceipt(ConnectionInterface $conn, Frame $frame)
    {
        $headers = $frame->getHeaders();
        if (isset($headers['receipt'])) {
            $response = $this->frames->getReceiptFrame($headers['receipt']);
            $conn->send($response);
            $this->getStorage()->pushToStack(
                $this->serverId,
                $frame->getCommand(),
                $frame->__toString(),
                $response->__toString()
            );
        }
    }
}
