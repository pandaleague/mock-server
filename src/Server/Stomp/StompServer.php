<?php

namespace PandaLeague\MockServer\Server\Stomp;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Stomp\Transport\Parser;

class StompServer implements MessageComponentInterface
{
    /** @var Parser  */
    protected $parser;

    /** @var StompInterface */
    private $manager;

    public function __construct(StompInterface $manager)
    {
        $this->parser = new Parser();
        $this->manager = $manager;
    }

    public function onOpen(ConnectionInterface $conn)
    {
    }

    public function onClose(ConnectionInterface $conn)
    {
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->parser->addData($msg);
        $frame = $this->parser->nextFrame();

        switch ($frame->getCommand()) {
            case 'CONNECT':
            case 'STOMP':
                $this->manager->onConnect($from, $frame);
                break;
            case 'SEND':
                $this->manager->onSend($from, $frame);
                break;
            case 'SUBSCRIBE':
                $this->manager->onSubscribe($from, $frame);
                break;
            case 'UNSUBSCRIBE':
                $this->manager->onUnSubscribe($from, $frame);
                break;
            case 'ACK':
                $this->manager->onAck($from, $frame);
                break;
            case 'NACK':
                $this->manager->onNack($from, $frame);
                break;
            case 'BEGIN':
                $this->manager->onBegin($from, $frame);
                break;
            case 'COMMIT':
                $this->manager->onCommit($from, $frame);
                break;
            case 'ABORT':
                $this->manager->onAbort($from, $frame);
                break;
            case 'DISCONNECT':
                $this->manager->onDisconnect($from, $frame);
                break;
            default:
                $this->manager->onHeartBeat($from, $frame);
                break;
        }
    }
}
