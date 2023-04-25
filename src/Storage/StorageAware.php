<?php

namespace PandaLeague\MockServer\Storage;

interface StorageAware
{
    public function setStorage(Storage $storage): void;
    public function getStorage(): Storage;
}
