<?php

namespace PandaLeague\MockServer\Server\Http;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use PandaLeague\MockServer\Storage\StorageAwareTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Http\NoOpHttpServerController;

class HttpManager extends NoOpHttpServerController implements HttpServerInterface
{
    use StorageAwareTrait;

    protected $serverId;

    public function __construct(string $serverId)
    {
        $this->serverId = $serverId;
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        // Invalid request object - return 500
        if (is_null($request)) {
            $response = new Response('500', [], 'Invalid request format');
            $this->log($request, $response);
            $conn->send(Message::toString($response));
            $conn->close();

            return;
        }

        // Check if the query parameter `_mock_delay` is set and sleep for that long in seconds
        // This is useful if you want to test timeouts
        parse_str($request->getUri()->getQuery(), $queryParams);
        if (isset($queryParams['_mock_delay'])) {
            sleep((int) $queryParams['_mock_delay']);
        }
        // Default routes: if the url is /xxx where xxx is an int, we will respond with
        // that status code. If xxx = 418 we will respond with a 418 status code
        $response = $this->requestX($request);
        if (! is_null($response)) {
            $this->log($request, $response);
            $conn->send(Message::toString($response));
            $conn->close();

            return;
        }

        // Check for any expectations and return the expectation if the request matches
        $expectation = $this->getMatchedExpectation($request);
        if (! is_null($expectation)) {
            $response = Message::parseResponse($expectation['response']);
            $this->getStorage()->expectationMatched((int)$expectation['id']);
            $this->log($request, $response, (int) $expectation['id']);

            if (count($response->getHeader('X-Delay'))) {
                sleep((int)$response->getHeader('X-Delay')[0]);
            }

            $conn->send(Message::toString($response));
            $conn->close();
            return;
        }

        // By Default we will send back a 404
        $response = new Response('404', [], 'Default response');
        $this->log($request, $response);
        $conn->send(Message::toString($response));
        $conn->close();

        return;
    }

    protected function log(
        RequestInterface $request = null,
        ResponseInterface $response = null,
        ?int $expectationId = null
    ) {
        if (! is_null($expectationId)) {
            $this->getStorage()->expectationMatched($expectationId);
        }

        $this->getStorage()->pushToStack(
            $this->serverId,
            $request == null ? 'GET' : $request->getMethod(),
            $request == null ? ''    : Message::toString($request),
            Message::toString($response),
            $expectationId
        );
    }

    protected function requestX(RequestInterface $request): ?ResponseInterface
    {
        $url = trim($request->getUri()->getPath(), '/');

        if (! is_numeric($url) || $url < 100 || $url >= 600) {
            return null;
        }

        return new Response($url, ['x-mock-server' => date('Y-m-d H:i:s')], $url);
    }

    public function getMatchedExpectation(RequestInterface $serverRequest): ?array
    {
        $expectations = $this->getStorage()->loadExpectations(
            $this->serverId,
            $serverRequest->getMethod(),
            true
        );

        $serverRequestHeaders = $serverRequest->getHeaders();

        foreach ($expectations as $expectation) {
            if ($expectation['request'] == '') {
                return $expectation;
            }

            $host = $serverRequestHeaders['Host'];
            $request = str_replace(
                "Host: \r\n",
                'Host: ' . implode(';', $host) . "\r\n",
                $expectation['request']
            );
            $request = Message::parseRequest($request);

            $match = true;
            // Check that the headers match. We only care about the ones defined in the expectation
            foreach ($request->getHeaders() as $header => $value) {
                if ($header == 'Host') {
                    continue;
                }

                if (
                    ! isset($serverRequestHeaders[$header])
                    || (
                        is_array($serverRequestHeaders[$header])
                        && is_array($value)
                        && implode('', $serverRequestHeaders[$header]) != implode('', $value)
                    ) || (
                        $serverRequestHeaders[$header] != $value
                    )
                ) {
                    $match = false;
                    break;
                }
            }

            if (! $match) {
                continue;
            }

            // Check if the body matches - empty will always match
            if (
                !empty($request->getBody()->__toString())
                && $request->getBody()->__toString() != $serverRequest->getBody()->__toString()
            ) {
                continue;
            }

            if ($serverRequest->getUri()->__toString() != $request->getUri()->__toString()) {
                continue;
            }

            return $expectation;
        }

        return null;
    }
}
