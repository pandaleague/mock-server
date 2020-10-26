<?php

declare(strict_types=1);

namespace PandaLeague\MockServer\Server\Stomp;

use Stomp\Protocol\Protocol;
use Stomp\Transport\Frame;

class StompFrames extends Protocol
{
    public function getConnectedFrame(
        $heartBeat = [0,0],
        $sessionId = null
    ): Frame {
        $frame = $this->createFrame('CONNECTED');
        $frame['version'] = $this->getVersion();
        $frame['server'] = $this->getServer();
        $frame['heart-beat'] = $heartBeat[0] . ',' . $heartBeat[1];
        $frame['session'] = $sessionId;

        return $frame;
    }

    public function getErrorFrame(string $message, ?Frame $originalFrame = null)
    {
        $frame = $this->createFrame('ERROR');
        $frame['message'] = $message;
        if ($originalFrame !== null) {
            if (isset($originalFrame->getHeaders()['receipt'])) {
                $frame['receipt-id'] = $originalFrame->getHeaders()['receipt'];
            }

            $frame->body = sprintf("The message\n------\n%s\n------\n%s", $originalFrame, $message);
        }

        return $frame;
    }

    public function getNotAuthedErrorFrame(): Frame
    {
        return $this->getErrorFrame('Invalid Username or Password');
    }

    public function getReceiptFrame(string $receiptId): Frame
    {
        $frame = $this->createFrame('RECEIPT');
        $frame['receipt-id'] = $receiptId;

        return $frame;
    }

    public function getMessageFrame(string $messageId, string $destination, $body, bool $isAck = true)
    {
        $frame = $this->createFrame('MESSAGE');
        $frame['message-id'] = $messageId;
        $frame['destination'] = $destination;
        $frame['subscription'] = '%id%';
        if ($isAck) {
            $frame['ack'] = time();
        }

        if (is_array($body)) {
            $body = json_encode($body);
            $frame['content-type'] = 'application/json';
        } else {
            $frame['content-type'] = 'text/plain';
        }
        $frame->body = $body;

        return $frame;
    }
}
