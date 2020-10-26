<?php

namespace PandaLeague\MockServer\Storage;

interface Storage
{
    /**
     * @return array
     */
    public function getConnectionParameters(): array;

    /**
     * @param string $connectionId
     * @param string|null $method
     * @param bool $excludeCalled
     * @return array
     */
    public function loadExpectations(string $connectionId, string $method = null, bool $excludeCalled = false): array;

    /**
     * @param int $id
     * @return mixed
     */
    public function expectationMatched(int $id);

    /**
     * @param string $connectionId
     * @param string $method
     * @param string $request
     * @param string $response
     * @param int|null $expectationId
     * @return int
     */
    public function pushToStack(
        string $connectionId,
        string $method,
        string $request,
        string $response,
        int $expectationId = null
    ): int;

    /**
     * @param string $connectionId
     * @param string $method
     * @param string $request
     * @param string $response
     * @param int $numberOfExpectedCalls
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function addExpectation(
        string $connectionId,
        string $method,
        string $response,
        ?string $request = '',
        int $numberOfExpectedCalls = 0
    ): int;

    /**
     * @return mixed
     */
    public function clearExpectations();

    /**
     * @return mixed
     */
    public function clearStack();

    /**
     * @return mixed
     */
    public function clearAll();
}
