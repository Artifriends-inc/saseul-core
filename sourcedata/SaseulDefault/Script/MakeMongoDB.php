<?php

namespace Saseul\Script;

use MongoDB\Driver;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Saseul\Common\Script;

/**
 * Class MakeMongoDB.
 *
 * composer 스크립트를 사용하기 위해서 작성하였다.
 *
 * @todo    이 함수를 여기가 두어서는 안된다.
 */
class MakeMongoDB extends Script
{
    private $manager;
    private $dbCommitted;
    private $dbTracker;

    public function __construct()
    {
        $this->manager = new Manager('mongodb://mongo');

        $this->dbCommitted = 'saseul_committed';
        $this->dbTracker = 'saseul_tracker';
    }

    public function _process(): void
    {
        $this->createDatabases();
        $this->createIndex();
    }

    private function executeCommand(string $db, array $commands): void
    {
        foreach ($commands as $command) {
            try {
                $this->manager->executeCommand($db, $command);
            } catch (Driver\Exception\Exception $exception) {
                echo $exception->getMessage();
            }
        }
    }

    private function createDatabases(): void
    {
        $committed = [
            new Command(['create' => 'generations']),
            new Command(['create' => 'blocks']),
            new Command(['create' => 'transactions']),
            new Command(['create' => 'coin']),
            new Command(['create' => 'attributes']),
            new Command(['create' => 'contract']),
            new Command(['create' => 'token']),
            new Command(['create' => 'token_list']),
        ];
        $tracker = [
            new Command(['create' => 'tracker']),
        ];

        $this->executeCommand($this->dbCommitted, $committed);
        $this->executeCommand($this->dbTracker, $tracker);

        echo 'Complete Create DB';
    }

    private function createIndex(): void
    {
        $transactionsIndex = new Command([
            'createIndexes' => 'transactions',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'thash' => 1], 'name' => 'timestamp_thash_asc'],
                ['key' => ['thash' => 1, 'timestamp' => 1], 'name' => 'thash_timestamp_unique', 'unique' => 1],
            ]
        ]);
        $generationsIndex = new Command([
            'createIndexes' => 'generations',
            'indexes' => [
                ['key' => ['origin_block_number' => 1], 'name' => 'origin_block_number_unique', 'unique' => 1],
            ]
        ]);
        $blocksIndex = new Command([
            'createIndexes' => 'blocks',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['block_number' => 1], 'name' => 'block_number_asc'],
            ]
        ]);
        $coinIndex = new Command([
            'createIndexes' => 'coin',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);
        $attributesIndex = new Command([
            'createIndexes' => 'attributes',
            'indexes' => [
                ['key' => ['address' => 1, 'key' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);
        $contractIndex = new Command([
            'createIndexes' => 'contract',
            'indexes' => [
                ['key' => ['cid' => 1], 'name' => 'cid_asc'],
                ['key' => ['chash' => 1], 'name' => 'chash_asc'],
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'chash' => 1], 'name' => 'timestamp_chash_asc'],
            ]
        ]);
        $tokenIndex = new Command([
            'createIndexes' => 'token',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_asc'],
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc'],
                ['key' => ['address' => 1, 'token_name' => 1], 'name' => 'address_token_name_asc', 'unique' => 1],
            ]
        ]);
        $tokenListIndex = new Command([
            'createIndexes' => 'token_list',
            'indexes' => [
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc', 'unique' => 1],
            ]
        ]);

        $trackerIndex = new Command([
            'createIndexes' => 'tracker',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);

        $committed = [
            $transactionsIndex,
            $generationsIndex,
            $blocksIndex,
            $coinIndex,
            $attributesIndex,
            $contractIndex,
            $tokenIndex,
            $tokenListIndex,
        ];
        $tracker = [
            $trackerIndex,
        ];

        $this->executeCommand($this->dbCommitted, $committed);
        $this->executeCommand($this->dbTracker, $tracker);

        echo 'Complete Create Index';
    }
}
