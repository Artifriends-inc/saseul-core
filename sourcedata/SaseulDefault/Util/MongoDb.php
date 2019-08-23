<?php

namespace Saseul\Util;

use Exception;
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
class MongoDb
{
    public $manager;
    public $bulk;

    protected $db_host;
    protected $db_port;
    protected $db_name;

    protected $m_db;
    protected $m_namespace;
    protected $m_query;
    protected $m_command;

    protected static $instance = null;
    private $logger;

    public function __construct()
    {
        $this->Init();
        $this->logger = Logger::getLogger('mongodb');

        $this->manager = new Manager("mongodb://{$this->db_host}:{$this->db_port}");
        $this->bulk = new BulkWrite();
    }

    /**
     * Initialize the DB.
     */
    public function Init(): void
    {
        $this->db_host = Env::$mongoDb['host'];
        $this->db_port = Env::$mongoDb['port'];
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

    public function Query($namespace, $query_filter, $query_options = []): Cursor
    {
        $this->m_namespace = $namespace;
        $this->m_query = new Query($query_filter, $query_options);

        return $this->manager->executeQuery($this->m_namespace, $this->m_query);
    }

    public function Command($db, $command_document): Cursor
    {
        $this->m_db = $db;
        $this->m_command = new Command($command_document);

        return $this->manager->executeCommand($this->m_db, $this->m_command);
    }

    public function executeBulkCommand(string $db, array $commands): void
    {
        foreach ($commands as $command) {
            try {
                $this->manager->executeCommand($db, $command);
            } catch (Driver\Exception\Exception $exception) {
                $this->logger->err($exception->getMessage());
            }
        }
    }

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
