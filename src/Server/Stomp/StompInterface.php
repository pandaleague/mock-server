<?php

declare(strict_types=1);

namespace PandaLeague\MockServer\Server\Stomp;

use Ratchet\ConnectionInterface;
use Stomp\Transport\Frame;

interface StompInterface
{
    public function onConnect(ConnectionInterface $conn, Frame $frame);

    public function onSend(ConnectionInterface $conn, Frame $frame);

    public function onSubscribe(ConnectionInterface $conn, Frame $frame);

    public function onUnSubscribe(ConnectionInterface $conn, Frame $frame);

    public function onBegin(ConnectionInterface $conn, Frame $frame);

    public function onCommit(ConnectionInterface $conn, Frame $frame);

    public function onAbort(ConnectionInterface $conn, Frame $frame);

    public function onAck(ConnectionInterface $conn, Frame $frame);

    public function onNack(ConnectionInterface $conn, Frame $frame);

    public function onDisconnect(ConnectionInterface $conn, Frame $frame);

    public function onHeartBeat(ConnectionInterface $conn, Frame $frame);
}
