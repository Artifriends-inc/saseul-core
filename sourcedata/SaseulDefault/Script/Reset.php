<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Rank;
use Saseul\Core\Generation;
use Saseul\Core\Property;
use Saseul\Core\Env;
use Saseul\Core\Service;
use Saseul\Core\Tracker;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\Util\File;
use Saseul\Util\Logger;

class Reset extends Script
{
    private $db;
    private $cache;
    private $patch_contract;
    private $patch_exchange;
    private $patch_token;
    private $patch_auth_token;

    private $noAsk;

    public function __construct($noAsk = false)
    {
        $this->db = Database::GetInstance();
        $this->cache = Cache::GetInstance();
        $this->patch_contract = new Patch\Contract();
        $this->patch_exchange = new Patch\Exchange();
        $this->patch_token = new Patch\Token();
        $this->patch_auth_token = new Patch\AuthToken();

        $this->noAsk = $noAsk;
    }

    public function _process()
    {
        if (isset($this->arg[0]) && $this->arg[0] === '-r') {
            $this->noAsk = true;
        }

        if ($this->noAsk === false) {
            if ($this->ask('Reset? [y/n] ') !== 'y') {
                return;
            }
        }

        if (Service::isDaemonRunning() === true) {
            Property::isReady(false);

            for ($i = 1; $i <= 20; $i++) {
                if (Property::isRoundRunning() === false) {
                    break;
                }
                Logger::EchoLog("waiting round ... ({$i})");
                sleep(1);
            }
        }

        sleep(2);
        $this->DeleteFiles();
        $this->FlushCache();
        $this->DropDatabase();
        $this->CreateDatabase();
        $this->CreateIndex();
        $this->CreateGenesisTracker();
        $this->patch_contract->Exec();
        $this->patch_exchange->Exec();
        $this->patch_token->Exec();
        $this->patch_auth_token->Exec();
        $this->RestoreOriginalSource();
        sleep(2);

        Property::init();
        Generation::makeSourceArchive();
        Logger::EchoLog('Success');
    }

    public function RestoreOriginalSource()
    {
        $original = Directory::ORIGINAL_SOURCE;
        $saseulSource = Directory::SASEUL_SOURCE;

        if (file_exists($saseulSource)) {
            unlink($saseulSource);
        }

        symlink($original, $saseulSource);
    }

    public function DeleteFiles()
    {
        Logger::EchoLog('Delete Files : API Chunk ');
        File::rrmdir(Directory::API_CHUNKS);
        mkdir(Directory::API_CHUNKS);
        chmod(Directory::API_CHUNKS, 0775);

        Logger::EchoLog('Delete Files : Broadcast Chunk ');
        File::rrmdir(Directory::BROADCAST_CHUNKS);
        mkdir(Directory::BROADCAST_CHUNKS);
        chmod(Directory::BROADCAST_CHUNKS, 0775);

        Logger::EchoLog('Delete Files : Transactions ');
        File::rrmdir(Directory::TRANSACTIONS);
        mkdir(Directory::TRANSACTIONS);
        chmod(Directory::TRANSACTIONS, 0775);

        Logger::EchoLog('Delete Files : Transaction Archive ');
        File::rrmdir(Directory::TX_ARCHIVE);
        mkdir(Directory::TX_ARCHIVE);
        chmod(Directory::TX_ARCHIVE, 0775);

        Logger::EchoLog('Delete Files : Generations ');
        File::rrmdir(Directory::GENERATIONS);
        mkdir(Directory::GENERATIONS);
        chmod(Directory::GENERATIONS, 0775);

        Logger::EchoLog('Delete Files : Temp folder ');
        File::rrmdir(Directory::TEMP);
        mkdir(Directory::TEMP);
        chmod(Directory::TEMP, 0775);
    }

    public function FlushCache()
    {
        $this->cache->flush();
    }

    public function DropDatabase()
    {
        Logger::EchoLog('Drop Database');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['dropDatabase' => 1]);
        $this->db->Command(MongoDbConfig::DB_TRACKER, ['dropDatabase' => 1]);
    }

    public function CreateDatabase()
    {
        Logger::EchoLog('Create Database');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'generations']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'blocks']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'transactions']);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'coin']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'attributes']);

        $this->db->Command(MongoDbConfig::DB_TRACKER, ['create' => 'tracker']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'transactions',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'thash' => 1], 'name' => 'timestamp_thash_asc'],
                ['key' => ['thash' => 1, 'timestamp' => 1], 'name' => 'thash_timestamp_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'generations',
            'indexes' => [
                ['key' => ['origin_block_number' => 1], 'name' => 'origin_block_number_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'blocks',
            'indexes' => [
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['block_number' => 1], 'name' => 'block_number_asc'],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'coin',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'attributes',
            'indexes' => [
                ['key' => ['address' => 1, 'key' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_TRACKER, [
            'createIndexes' => 'tracker',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_unique', 'unique' => 1],
            ]
        ]);
    }

    public function CreateGenesisTracker()
    {
        Logger::EchoLog('CreateGenesisTracker');
        Tracker::reset();
    }
}
