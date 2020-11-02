<?php

namespace PandaLeague\MockServer\Storage\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PandaLeague\MockServer\Storage\ConnectionAware;
use PandaLeague\MockServer\Storage\ConnectionAwareTrait;
use PandaLeague\MockServer\Storage\Storage;

class DoctrineStorage implements Storage, ConnectionAware
{
    use ConnectionAwareTrait;

    /**
     * @var array
     */
    private $connectionParams;

    /**
     * DoctrineStorage constructor.
     * @param array $connectionParams
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(array $connectionParams)
    {
        $this->setConnection(DriverManager::getConnection($connectionParams));
        $this->createTables();
        $this->connectionParams = $connectionParams;
    }

    /**
     * @return array
     */
    public function getConnectionParameters(): array
    {
        return $this->connectionParams;
    }

    /**
     * @param string $tableName
     * @return bool
     */
    private function tableExist(string $tableName): bool
    {
        $tables = $this->connection->getSchemaManager()->listTables();

        foreach ($tables as $table) {
            if ($table->getName() == $tableName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Schema $schema
     */
    private function createExpectationsTable(Schema $schema)
    {
        if ($this->tableExist('expectations')) {
            return;
        }

        $expectationTable = $schema->createTable('expectations');
        $expectationTable->addColumn('id', 'integer')
            ->setUnsigned(true)
            ->setNotnull(true)
            ->setAutoincrement(true);
        $expectationTable->setPrimaryKey(['id']);
        $expectationTable->addColumn('connection_id', 'string')
            ->setLength(250);
        $expectationTable->addColumn('method', 'string')
            ->setLength(250);
        $expectationTable->addColumn('request', 'text')
            ->setNotnull(false);
        $expectationTable->addColumn('response', 'text');
        $expectationTable->addColumn('number_of_expected_calls', 'integer')
            ->setDefault(0);
        $expectationTable->addColumn('number_of_calls', 'integer')
            ->setDefault(0);
    }

    /**
     * @param Schema $schema
     */
    private function createCallStackTable(Schema $schema)
    {
        if ($this->tableExist('call_stack')) {
            return;
        }

        $callStack = $schema->createTable('call_stack');
        $callStack->addColumn('id', 'integer')
            ->setUnsigned(true)
            ->setNotnull(true)
            ->setAutoincrement(true);
        $callStack->setPrimaryKey(['id']);
        $callStack->addColumn('connection_id', 'string')
            ->setLength(250);
        $callStack->addColumn('method', 'string')
            ->setLength(250);
        $callStack->addColumn('request', 'text');
        $callStack->addColumn('response', 'text');
        $callStack->addColumn('expectation_id', 'integer')
            ->setNotnull(false);
        $callStack->addColumn('date_stamp', 'datetime');
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function createTables()
    {
        //$schema = $this->connection->getSchemaManager()->createSchema();
        $schema = new Schema();

        $this->createExpectationsTable($schema);
        $this->createCallStackTable($schema);

        $sqlStack = $schema->toSql($this->connection->getDatabasePlatform());

        foreach ($sqlStack as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @param string $connectionId
     * @param string|null $method
     * @param bool $excludeCalled
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadExpectations(string $connectionId, string $method = null, bool $excludeCalled = false): array
    {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql = $sql->select(
            'id',
            'connection_id',
            'method',
            'request',
            'response',
            'number_of_expected_calls',
            'number_of_calls'
        )
            ->from('expectations')
            ->where('connection_id = :connection_id');

        $data = ['connection_id' => $connectionId];

        if (! is_null($method)) {
            $sql->andWhere('method = :method');
            $data['method'] = $method;
        }

        if ($excludeCalled) {
            $sql->andWhere('(number_of_expected_calls = 0 OR number_of_expected_calls > number_of_calls)');
        }

        $results = $this->getConnection()->executeQuery($sql->getSQL(), $data)->fetchAllAssociative();

//        foreach ($results as &$row) {
//            $row['request'] = base64_decode($row['request']);
//            $row['response'] = base64_decode($row['response']);
//        }

        return $results;
    }

    /**
     * @param int $id
     * @throws \Doctrine\DBAL\Exception
     */
    public function expectationMatched(int $id)
    {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql->update('expectations')
            ->set('number_of_calls', 'number_of_calls + 1')
            ->where('id = :id');

        $this->getConnection()->executeQuery($sql->getSQL(), [':id' => $id]);
    }

    /**
     * @param string $connectionId
     * @param string $method
     * @param string $request
     * @param string $response
     * @param int|null $expectationId
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function pushToStack(
        string $connectionId,
        string $method,
        string $request,
        string $response,
        int $expectationId = null
    ): int {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql->insert('call_stack')
            ->values([
                'method'         => ':method',
                'request'        => ':request',
                'response'       => ':response',
                'expectation_id' => ':expectation_id',
                'date_stamp'     => ':date_stamp',
                'connection_id'  => ':connection_id'
            ]);
        $data = [
            'method'         => $method,
            'request'        => $request,
            'response'       => $response,
            'expectation_id' => $expectationId,
            'date_stamp'     => date('Y-m-d H:i:s'),
            'connection_id'  => $connectionId
        ];

        $this->getConnection()->executeQuery($sql->getSQL(), $data);

        return $this->getConnection()->lastInsertId();
    }

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
    ): int {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql->insert('expectations')
            ->values([
                'method'                   => ':method',
                'request'                  => ':request',
                'response'                 => ':response',
                'number_of_expected_calls' => ':calls',
                'connection_id'            => ':connection_id'
            ]);
        $data = [
            'method'         => $method,
            'request'        => $request,
            'response'       => $response,
            'calls'          => $numberOfExpectedCalls,
            'connection_id'  => $connectionId
        ];

        $this->getConnection()->executeQuery($sql->getSQL(), $data);

        return $this->getConnection()->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function clearExpectations()
    {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql->delete('expectations');

        $this->getConnection()->executeQuery($sql->getSQL(), []);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function clearStack()
    {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql->delete('call_stack');

        $this->getConnection()->executeQuery($sql->getSQL(), []);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function clearAll()
    {
        $this->clearExpectations();
        $this->clearStack();
    }

    public function disconnect()
    {
        if ($this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public function loadStack(string $connectionId, ?string $method = null): array
    {
        $sql = $this->getConnection()->createQueryBuilder();
        $sql = $sql->select(
            'id',
            'connection_id',
            'method',
            'request',
            'response',
            'expectation_id',
            'date_stamp'
        )
            ->from('call_stack')
            ->where('connection_id = :connection_id');

        $data = ['connection_id' => $connectionId];

        if (! is_null($method)) {
            $sql->andWhere('method = :method');
            $data['method'] = $method;
        }

        return $this->getConnection()->executeQuery($sql->getSQL(), $data)->fetchAllAssociative();
    }
}
