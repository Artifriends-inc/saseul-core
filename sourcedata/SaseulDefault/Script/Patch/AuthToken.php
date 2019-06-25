<?php

namespace Saseul\Script\Patch;

use Saseul\Common\Script;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Database;
use Saseul\Util\Logger;

class AuthToken extends Script
{
    private $db;

    public function __construct()
    {
        $this->db = Database::GetInstance();
    }

    public function _process()
    {
        $this->CreateDatabase();
        $this->CreateIndex();
    }

    public function CreateDatabase()
    {
        Logger::EchoLog('Create Database');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'auth_token']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'auth_token_info']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'auth_token',
            'indexes' => [
                ['key' => ['tid' => 1], 'name' => 'tid_asc', 'unique' => 1],
                ['key' => ['owner' => 1], 'name' => 'owner_asc'],
                ['key' => ['code' => 1], 'name' => 'code_asc'],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'auth_token_info',
            'indexes' => [
                ['key' => ['code' => 1], 'name' => 'code_asc', 'unique' => 1],
            ]
        ]);
    }
}
