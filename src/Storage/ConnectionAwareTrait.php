<?php

namespace PandaLeague\MockServer\Storage;

use Doctrine\DBAL\Connection;

trait ConnectionAwareTrait
{
    /** @var Connection */
    protected $connection;

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
