<?php

declare(strict_types=1);

namespace PandaLeague\MockServer;

use Stomp\Transport\Frame;

class StompMock extends ServerMock
{
    public function expectsStomp(
        $method,
        Frame $response,
        ?Frame $request = null,
        int $exactTimes = 0
    ): int {
        return parent::expects(
            $method,
            $response->__toString(),
            is_null($request) ? '' : $request->__toString(),
            $exactTimes
        );
    }
}
