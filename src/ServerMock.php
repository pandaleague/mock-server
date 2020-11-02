<?php

namespace PandaLeague\MockServer;

use PandaLeague\MockServer\Storage\Storage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

class ServerMock extends Process
{
    /** @var int */
    private $port;

    /** @var string  */
    private $host;

    /** @var string */
    private $server;

    /** @var Storage */
    protected $storage;

    /** @var string */
    protected $serverId;

    /**
     * ServerMock constructor.
     * @param string $host
     * @param int $port
     * @param string $server This must be the classFQN of the class implementing
     *  Ratchet\MessageComponentInterface and PandaLeague\MockServer\Storage\StorageAware
     * @param Storage $storage A new instance of this will be created when the mock server starts
     */
    public function __construct(string $host, int $port, string $server, Storage $storage)
    {
        $this->port = $port;
        $this->host = $host;
        $this->server = $server;
        $this->storage = $storage;
        $this->serverId = Uuid::uuid4()->toString();

        $command = [
            'php',
            __DIR__ . '/../bin/mock.php',
            'start',
            $host,
            $port,
            $server,
            get_class($storage),
            json_encode($storage->getConnectionParameters()),
            $this->serverId
        ];

        parent::__construct($command, null, null, null, null);
    }

    public function expects($method, $response, $request = '', int $exactTimes = 0): int
    {
        return $this->storage->addExpectation(
            $this->serverId,
            $method,
            $response,
            $request,
            $exactTimes
        );
    }

    public function getServerId()
    {
        return $this->serverId;
    }
}
