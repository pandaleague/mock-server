<?php

namespace PandaLeague\MockServer\Server;

use PandaLeague\MockServer\Server\Http\HttpManager;
use PandaLeague\MockServer\Server\Stomp\StompManager;
use PandaLeague\MockServer\Server\Stomp\StompMockManager;
use PandaLeague\MockServer\Server\Stomp\StompServer;
use PandaLeague\MockServer\Storage\Storage;

class ServerFactory
{
    // Probably should be in a DI container...
    public static function create(string $serverName, string $serverId, Storage $storage)
    {
        switch ($serverName) {
            case StompServer::class:
                $manager = new StompManager($serverId);
                $manager->setStorage($storage);
                $mock = new StompMockManager($manager);
                $mock->setStorage($storage);
                return new StompServer($mock);
            case \Ratchet\Http\HttpServer::class:
                $manager = new HttpManager($serverId);
                $manager->setStorage($storage);
                return new \Ratchet\Http\HttpServer($manager);
        }
    }
}
