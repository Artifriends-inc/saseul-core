<?php

namespace Saseul\Util;

use Exception;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\ReadPreference;
use Saseul\Core\Env;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 */
class Mongo
{
    public const DB_TRACKER = 'saseul_tracker';
    public const DB_COMMITTED = 'saseul_committed';

    public const COLLECTION_TOKEN = 'token';
    public const COLLECTION_TRACKER = 'tracker';
    public const COLLECTION_ATTRIBUTES = 'attributes';
    public const COLLECTION_TOKEN_LIST = 'token_list';
    public const COLLECTION_CONTRACT = 'contract';
    public const COLLECTION_TRANSACTIONS = 'transactions';
    public const COLLECTION_GENERATIONS = 'generations';
    public const COLLECTION_COIN = 'coin';
    public const COLLECTION_BLOCKS = 'blocks';

    /**
     * @var Client MongoDB client 개체
     */
    public $client;

    /**
     * @deprecated Client 에서 같은 작업을 할수 있기에 우선은 사용하지 않을 예정입니다.
     *
     * @var Manager
     */
    public $manager;

    /**
     * @deprecated buk 항목을 Client에서 해당하는 작업으로 변경하여 작업할 예정입니다.
     *
     * @var BulkWrite
     */
    public $bulk;

    protected $m_db;
    protected $m_namespace;
    protected $m_query;
    protected $m_command;

    protected static $instance;
    private $logger;

    public function __construct()
    {
        $host = Env::$mongoDb['host'];
        $port = Env::$mongoDb['port'];

        $this->logger = Logger::getLogger(Logger::MONGO);

        $this->client = new Client("mongodb://{$host}:{$port}");
        $this->manager = new Manager("mongodb://{$host}:{$port}");
        $this->bulk = new BulkWrite();
    }

    /**
     * Committed DB 연결하여 사용한다.
     *
     * @return Database
     */
    public function getCommittedDB(): Database
    {
        return $this->client->selectDatabase(self::DB_COMMITTED);
    }

    /**
     * Tracker DB 연결하여 사용한다.
     *
     * @return Database
     */
    public function getTrackerDB(): Database
    {
        return $this->client->selectDatabase(self::DB_TRACKER);
    }

    /**
     * Generations 컬렉션을 연결하여 사용한다.
     *
     * @return Collection
     */
    public function getGenerationsCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_GENERATIONS);
    }

    /**
     * Blocks 컨렉션을 연결하여 사용한다.
     *
     * @return Collection
     */
    public function getBlocksCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_BLOCKS);
    }

    /**
     * Transactions 컨렉션을 연결하여 사용한다.
     *
     * @return Collection
     */
    public function getTransactionsCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_TRANSACTIONS);
    }

    /**
     * Coin 컨렉션을 연결하여 사용한다.
     *
     * @return Collection
     */
    public function getCoinCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_COIN);
    }

    /**
     * Attributes 컬렉션을 사용한다.
     *
     * @return Collection
     */
    public function getAttributesCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_ATTRIBUTES);
    }

    /**
     * Contract 컬렉션을 사용한다.
     *
     * @return Collection
     */
    public function getContractCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_CONTRACT);
    }

    /**
     * Token 컬렉션을 사용한다.
     *
     * @return Collection
     */
    public function getTokenCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_TOKEN);
    }

    /**
     * Token list 컬렉션을 사용한다.
     *
     * @return Collection
     */
    public function getTokenListCollection(): Collection
    {
        return $this->getCommittedDB()->selectCollection(self::COLLECTION_TOKEN_LIST);
    }

    /**
     * Tracker 컬렉션을 사용한다.
     *
     * @return Collection
     */
    public function getTrackerCollection(): Collection
    {
        return $this->getTrackerDB()->selectCollection(self::COLLECTION_TRACKER);
    }

    public function IsConnect(): bool
    {
        try {
            $server = $this->manager->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated Query 하는 부분을 refactoring 할 예정
     *
     * @param       $namespace
     * @param       $query_filter
     * @param array $query_options
     *
     * @throws Driver\Exception\Exception
     *
     * @return Cursor
     */
    public function Query($namespace, $query_filter, $query_options = []): Cursor
    {
        $this->m_namespace = $namespace;
        $this->m_query = new Query($query_filter, $query_options);

        return $this->manager->executeQuery($this->m_namespace, $this->m_query);
    }

    /**
     * @deprecated Command를 사용하는 부분을 직접 client를 사용하도록 할 예정
     *
     * @param $db
     * @param $command_document
     *
     * @throws Driver\Exception\Exception
     *
     * @return Cursor
     */
    public function Command($db, $command_document): Cursor
    {
        $this->m_db = $db;
        $this->m_command = new Command($command_document);

        return $this->manager->executeCommand($this->m_db, $this->m_command);
    }

    /**
     * @deprecated refactoring 할 예정
     *
     * @param      $namespace
     * @param null $bulk
     */
    public function BulkWrite($namespace, $bulk = null): void
    {
        if ($bulk === null) {
            $this->manager->executeBulkWrite($namespace, $this->bulk);
            $this->bulk = new BulkWrite();
        } else {
            $this->manager->executeBulkWrite($namespace, $bulk);
        }
    }
}
