<?php

namespace Saseul\Common;

use MongoDB\Driver\Command;
use Saseul\Constant\MongoDbConfig;
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
            ['create' => MongoDbConfig::COLLECTION_GENERATION],
            ['create' => MongoDbConfig::COLLECTION_BLOCK],
            ['create' => MongoDbConfig::COLLECTION_TRANSACTION],
            ['create' => MongoDbConfig::COLLECTION_COIN],
            ['create' => MongoDbConfig::COLLECTION_ATTRIBUTE],
            ['create' => MongoDbConfig::COLLECTION_CONTRACT],
            ['create' => MongoDbConfig::COLLECTION_TOKEN],
            ['create' => MongoDbConfig::COLLECTION_TOKEN_LIST],
        ];
        $committed = self::makeCommand($committedCollections);

        $tracker = [
            new Command(['create' => MongoDbConfig::COLLECTION_TRACKER]),
        ];

        $db->executeBulkCommand(MongoDbConfig::DB_COMMITTED, $committed);
        $db->executeBulkCommand(MongoDbConfig::DB_TRACKER, $tracker);
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
                'createIndexes' => 'transactions',
                'indexes' => [
                    ['key' => ['timestamp' => MongoDbConfig::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDbConfig::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['timestamp' => MongoDbConfig::ASC, 'thash' => MongoDbConfig::ASC], 'name' => 'timestamp_thash_asc'],
                    ['key' => ['thash' => MongoDbConfig::ASC, 'timestamp' => MongoDbConfig::ASC], 'name' => 'thash_timestamp_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => 'generations',
                'indexes' => [
                    ['key' => ['origin_block_number' => MongoDbConfig::ASC], 'name' => 'origin_block_number_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => 'blocks',
                'indexes' => [
                    ['key' => ['timestamp' => MongoDbConfig::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDbConfig::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['block_number' => MongoDbConfig::ASC], 'name' => 'block_number_asc'],
                ]
            ],
            [
                'createIndexes' => 'coin',
                'indexes' => [
                    ['key' => ['address' => MongoDbConfig::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => 'attributes',
                'indexes' => [
                    ['key' => ['address' => MongoDbConfig::ASC, 'key' => MongoDbConfig::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => 'contract',
                'indexes' => [
                    ['key' => ['cid' => MongoDbConfig::ASC], 'name' => 'cid_asc'],
                    ['key' => ['chash' => MongoDbConfig::ASC], 'name' => 'chash_asc'],
                    ['key' => ['timestamp' => MongoDbConfig::ASC], 'name' => 'timestamp_asc'],
                    ['key' => ['timestamp' => MongoDbConfig::DESC], 'name' => 'timestamp_desc'],
                    ['key' => ['timestamp' => MongoDbConfig::ASC, 'chash' => MongoDbConfig::ASC], 'name' => 'timestamp_chash_asc'],
                ]
            ],
            [
                'createIndexes' => 'token',
                'indexes' => [
                    ['key' => ['address' => MongoDbConfig::ASC], 'name' => 'address_asc'],
                    ['key' => ['token_name' => MongoDbConfig::ASC], 'name' => 'token_name_asc'],
                    ['key' => ['address' => MongoDbConfig::ASC, 'token_name' => MongoDbConfig::ASC], 'name' => 'address_token_name_asc', 'unique' => true],
                ]
            ],
            [
                'createIndexes' => 'token_list',
                'indexes' => [
                    ['key' => ['token_name' => MongoDbConfig::ASC], 'name' => 'token_name_asc', 'unique' => true],
                ]
            ]
        ];
        $committed = self::makeCommand($committedIndex);

        $trackerIndex = [
            [
                'createIndexes' => 'tracker',
                'indexes' => [
                    ['key' => ['address' => MongoDbConfig::ASC], 'name' => 'address_unique', 'unique' => true],
                ]
            ]
        ];
        $tracker = self::makeCommand($trackerIndex);

        $db->executeBulkCommand(MongoDbConfig::DB_COMMITTED, $committed);
        $db->executeBulkCommand(MongoDbConfig::DB_TRACKER, $tracker);
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
