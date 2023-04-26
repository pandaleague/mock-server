<?php

namespace PandaLeague\MockServer\Storage;

trait StorageAwareTrait
{
    /** @var ?Storage */
    private $storage;

    /**
     * @param Storage $storage
     */
    public function setStorage(Storage $storage): void
    {
        $this->storage = $storage;
    }

    /**
     * @return Storage
     */
    public function getStorage(): Storage
    {
        if (! isset($this->storage)) {
            throw new \RuntimeException('No storage has been set');
        }

        return $this->storage;
    }
}
