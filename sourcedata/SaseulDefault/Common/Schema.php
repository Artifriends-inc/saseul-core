<?php

namespace Saseul\Common;

use MongoDB\Driver\Command;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;

/**
 * Class Schema.
 *
 * @todo: Test 가 필요하다.
 */
class Schema
{
    /**
     * @codeCoverageIgnore
     *
     * MongoDB에 있는 내용을 drop 한다.
     *
     * @param Database $db
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function dropDatabaseOnMongoDB(Database $db): void
    {
        $db->manager->executeCommand(MongoDbConfig::DB_COMMITTED, new Command(['dropDatabase' => 1]));
        $db->manager->executeCommand(MongoDbConfig::DB_TRACKER, new Command(['dropDatabase' => 1]));
    }

    /**
     * @codeCoverageIgnore
     *
     * MongoDB 에서 Database 를 생성한다.
     *
     * @param Database $db
     *
     * @throws \Exception
     */
    public static function createDatabaseOnMongoDB(Database $db): void
    {
        $committedCollections = [
            ['create' => MongoDb::COLLECTION_GENERATIONS],
            ['create' => MongoDb::COLLECTION_BLOCKS],
            ['create' => MongoDb::COLLECTION_TRANSACTIONS],
            ['create' => MongoDb::COLLECTION_COIN],
            ['create' => MongoDb::COLLECTION_ATTRIBUTES],
            ['create' => MongoDb::COLLECTION_CONTRACT],
            ['create' => MongoDb::COLLECTION_TOKEN],
            ['create' => MongoDb::COLLECTION_TOKEN_LIST],
        ];
        $committed = self::makeCommand($committedCollections);

        $tracker = [
            new Command(['create' => MongoDb::COLLECTION_TRACKER]),
        ];

        $db->executeBulkCommand(MongoDb::DB_COMMITTED, $committed);
        $db->executeBulkCommand(MongoDb::DB_TRACKER, $tracker);
    }

    /**
     * @codeCoverageIgnore
     *
     * MongoDB 에 Index 를 추가한다.
     *
     * @param Database $db
     *
     * @throws \Exception
     */
    public static function createIndexOnMongoDB(Database $db): void
    {
        $committedIndex = [
            [
                'createIndexes' => MongoDb::COLLECTION_TRANSACTIONS,
                'indexes' => [
                    ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['timestamp' => MongoDb::ASC, 'thash' => MongoDb::ASC], 'name' => 'timestamp_thash_asc'],
                    ['key' => ['thash' => MongoDb::ASC, 'timestamp' => MongoDb::ASC], 'name' => 'thash_timestamp_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_GENERATIONS,
                'indexes' => [
                    ['key' => ['origin_block_number' => MongoDb::ASC], 'name' => 'origin_block_number_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_BLOCKS,
                'indexes' => [
                    ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['block_number' => MongoDb::ASC], 'name' => 'block_number_asc'],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_COIN,
                'indexes' => [
                    ['key' => ['address' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_ATTRIBUTES,
                'indexes' => [
                    ['key' => ['address' => MongoDb::ASC, 'key' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_CONTRACT,
                'indexes' => [
                    ['key' => ['cid' => MongoDb::ASC], 'name' => 'cid_asc'],
                    ['key' => ['chash' => MongoDb::ASC], 'name' => 'chash_asc'],
                    ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['timestamp' => MongoDb::ASC, 'chash' => MongoDb::ASC], 'name' => 'timestamp_chash_asc'],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_TOKEN,
                'indexes' => [
                    ['key' => ['address' => MongoDb::ASC], 'name' => 'address_asc'],
                    ['key' => ['token_name' => MongoDb::ASC], 'name' => 'token_name_asc'],
                    ['key' => ['address' => MongoDb::ASC, 'token_name' => MongoDb::ASC], 'name' => 'address_token_name_asc', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => MongoDb::COLLECTION_TOKEN_LIST,
                'indexes' => [
                    ['key' => ['token_name' => MongoDb::ASC], 'name' => 'token_name_asc', 'unique' => true],
                ]
            ]
        ];
        $committed = self::makeCommand($committedIndex);

        $trackerIndex = [
            [
                'createIndexes' => MongoDb::COLLECTION_TRACKER,
                'indexes' => [
                    ['key' => ['address' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ]
        ];
        $tracker = self::makeCommand($trackerIndex);

        $db->executeBulkCommand(MongoDb::DB_COMMITTED, $committed);
        $db->executeBulkCommand(MongoDb::DB_TRACKER, $tracker);
    }

    private static function makeCommand(array $inCommand): array
    {
        $outCommand = [];
        foreach ($inCommand as $item) {
            $outCommand[] = new Command($item);
        }

        return $outCommand;
    }
}
