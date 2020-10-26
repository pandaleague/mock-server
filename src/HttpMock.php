<?php

declare(strict_types=1);

namespace PandaLeague\MockServer;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpMock extends ServerMock
{
    public function expectsHttp(
        $method,
        ResponseInterface $response,
        ?RequestInterface $request = null,
        int $exactTimes = 0
    ): int {
        return parent::expects(
            $method,
            Message::toString($response),
            is_null($request) ? '' : Message::toString($request),
            $exactTimes
        );
    }
}
