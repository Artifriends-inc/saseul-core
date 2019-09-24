<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Core\Generation;
use Saseul\Core\Property;
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
    private $patch_token;
    private $patch_auth_token;

    private $noAsk;

    public function __construct($noAsk = false)
    {
        $this->db = Database::GetInstance();
        $this->cache = Cache::GetInstance();
        $this->patch_contract = new Patch\Contract();
        $this->patch_token = new Patch\Token();
        $this->patch_auth_token = new Patch\AuthToken();

        $this->noAsk = $noAsk;
    }

    public function _process(): void
    {
        if (isset($this->arg[0]) && $this->arg[0] === '-r') {
            $this->noAsk = true;
        }

        if ($this->noAsk === false) {
            if ($this->ask('Reset? [y/n] ') !== 'y') {
                return;
            }
        }

        // Round 가 끝나길 기다린다.
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

        // Patch DB 정보를 넣는다.
        // Todo: 해당 부분은 그냥 묶어서 넣어도 된다.
        $this->patch_contract->Exec();
        $this->patch_token->Exec();
        // Todo: AuthToken 부분은 삭제한다.
        $this->patch_auth_token->Exec();

        // Source 를 업데이트 한다.
        $this->RestoreOriginalSource();
        sleep(2);

        Logger::EchoLog('Set property');
        Property::init();

        Logger::EchoLog('Source archive');
        Generation::makeSourceArchive();

        Logger::EchoLog('Success');
    }

    /**
     * source data dir 를 업데이트 한다.
     */
    public function RestoreOriginalSource(): void
    {
        $original = Directory::RELATIVE_ORIGINAL_SOURCE;
        $saseulSource = Directory::SASEUL_SOURCE;

        if (file_exists($saseulSource)) {
            unlink($saseulSource);
        }

        symlink($original, $saseulSource);
    }

    /**
     * Block 파일을 삭제한다.
     */
    public function DeleteFiles(): void
    {
        Logger::EchoLog('Delete Files : API Chunk ');
        File::rrmdir(Directory::API_CHUNKS);
        mkdir(Directory::API_CHUNKS);
        chmod(Directory::API_CHUNKS, 0775);
        file_put_contents(Directory::API_CHUNKS . '/.keep', '');

        Logger::EchoLog('Delete Files : Broadcast Chunk ');
        File::rrmdir(Directory::BROADCAST_CHUNKS);
        mkdir(Directory::BROADCAST_CHUNKS);
        chmod(Directory::BROADCAST_CHUNKS, 0775);
        file_put_contents(Directory::BROADCAST_CHUNKS . '/.keep', '');

        Logger::EchoLog('Delete Files : Transactions ');
        File::rrmdir(Directory::TRANSACTIONS);
        mkdir(Directory::TRANSACTIONS);
        chmod(Directory::TRANSACTIONS, 0775);
        file_put_contents(Directory::TRANSACTIONS . '/.keep', '');

        Logger::EchoLog('Delete Files : Transaction Archive ');
        File::rrmdir(Directory::TX_ARCHIVE);
        mkdir(Directory::TX_ARCHIVE);
        chmod(Directory::TX_ARCHIVE, 0775);
        file_put_contents(Directory::TX_ARCHIVE . '/.keep', '');

        Logger::EchoLog('Delete Files : Generations ');
        File::rrmdir(Directory::GENERATIONS);
        mkdir(Directory::GENERATIONS);
        chmod(Directory::GENERATIONS, 0775);
        file_put_contents(Directory::GENERATIONS . '/.keep', '');

        Logger::EchoLog('Delete Files : Temp folder ');
        File::rrmdir(Directory::TEMP);
        mkdir(Directory::TEMP);
        chmod(Directory::TEMP, 0775);
        file_put_contents(Directory::TEMP . '/.keep', '');
    }

    /**
     * Memcached 저장된 값들을 정리한다.
     */
    public function FlushCache(): void
    {
        $this->cache->flush();
    }

    /**
     * MongoDB 데이타를 삭제한다.
     */
    public function DropDatabase(): void
    {
        Logger::EchoLog('Drop Database');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['dropDatabase' => 1]);
        $this->db->Command(MongoDbConfig::DB_TRACKER, ['dropDatabase' => 1]);
    }

    /**
     * 사용한 DB 를 생성한다.
     */
    public function CreateDatabase(): void
    {
        Logger::EchoLog('Create Database');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'generations']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'blocks']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'transactions']);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'coin']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'attributes']);

        $this->db->Command(MongoDbConfig::DB_TRACKER, ['create' => 'tracker']);
    }

    /**
     * MongoDB 에 Index 를 설정한다.
     */
    public function CreateIndex(): void
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

    /**
     * Genesis Tracker 를 생성한다.
     */
    public function CreateGenesisTracker(): void
    {
        Logger::EchoLog('Create Genesis Tracker');
        Tracker::reset();
    }
}
