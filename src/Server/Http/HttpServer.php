<?php
namespace PandaLeague\MockServer\Server\Http;
use Ratchet\Http\HttpServerInterface;

class HttpServer extends \Ratchet\Http\HttpServer
{
    public function __construct(HttpServerInterface $component, int $maxSize = 65536) {
        parent::__construct($component);
        $this->_reqParser->maxSize = $maxSize;
    }
}
