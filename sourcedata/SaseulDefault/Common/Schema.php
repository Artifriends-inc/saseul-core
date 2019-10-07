<?php

namespace Saseul\Common;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;
use Saseul\Util\Logger;
use Saseul\Util\Mongo;

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
     * @throws \Exception
     *
     * @return bool
     */
    public static function dropDatabaseOnMongoDB(Database $db): bool
    {
        $dropCommited = $db->getCommittedDB()->drop();
        $dropTracker = $db->getTrackerDB()->drop();

        $logger = Logger::getLogger(Logger::MONGO);
        $logger->debug('drop committed table', [$dropCommited, $dropCommited['ok']]);
        $logger->debug('drop trakcer table', [$dropTracker, $dropTracker['ok']]);

        return $dropCommited['ok'] && $dropTracker['ok'];
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
        $db->getCommittedCollections()[Mongo::COLLECTION_TRANSACTIONS]->createIndexes([
            ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
            ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
            ['key' => ['timestamp' => MongoDb::ASC, 'thash' => MongoDb::ASC], 'name' => 'timestamp_thash_asc'],
            ['key' => ['thash' => MongoDb::ASC, 'timestamp' => MongoDb::ASC], 'name' => 'thash_timestamp_unique', 'unique' => true],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_GENERATIONS]->createIndexes([
            ['key' => ['origin_block_number' => MongoDb::ASC], 'name' => 'origin_block_number_unique', 'unique' => true],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_BLOCKS]->createIndexes([
            ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
            ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
            ['key' => ['block_number' => MongoDb::ASC], 'name' => 'block_number_asc'],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_COIN]->createIndexes([
            ['key' => ['address' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_ATTRIBUTES]->createIndexes([
            ['key' => ['address' => MongoDb::ASC, 'key' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_CONTRACT]->createIndexes([
            ['key' => ['cid' => MongoDb::ASC], 'name' => 'cid_asc'],
            ['key' => ['chash' => MongoDb::ASC], 'name' => 'chash_asc'],
            ['key' => ['timestamp' => MongoDb::ASC], 'name' => 'timestamp_asc'],
            ['key' => ['timestamp' => MongoDb::DESC], 'name' => 'timestamp_desc'],
            ['key' => ['timestamp' => MongoDb::ASC, 'chash' => MongoDb::ASC], 'name' => 'timestamp_chash_asc'],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_TOKEN]->createIndexes([
            ['key' => ['address' => MongoDb::ASC], 'name' => 'address_asc'],
            ['key' => ['token_name' => MongoDb::ASC], 'name' => 'token_name_asc'],
            ['key' => ['address' => MongoDb::ASC, 'token_name' => MongoDb::ASC], 'name' => 'address_token_name_asc', 'unique' => true],
        ]);
        $db->getCommittedCollections()[Mongo::COLLECTION_TOKEN_LIST]->createIndexes([
            ['key' => ['token_name' => MongoDb::ASC], 'name' => 'token_name_asc', 'unique' => true],
        ]);

        $db->getTrackerCollection()->createIndexes([
            ['key' => ['address' => MongoDb::ASC], 'name' => 'address_unique', 'unique' => true],
        ]);
    }
}
