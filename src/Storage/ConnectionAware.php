<?php

namespace PandaLeague\MockServer\Storage;

use Doctrine\DBAL\Connection;

interface ConnectionAware
{
    public function getConnection(): Connection;

    public function setConnection(Connection $connection);
}
