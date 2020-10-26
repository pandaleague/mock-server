<?php

namespace PandaLeague\MockServer\Storage;

interface StorageAware
{
    public function setStorage(Storage $storage);
    public function getStorage(): Storage;
}
