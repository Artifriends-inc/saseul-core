<?php

namespace Saseul\Script\Patch;

use Saseul\Common\Script;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Database;
use Saseul\Util\Logger;

class Token extends Script
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

        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'token']);
        $this->db->Command(MongoDbConfig::DB_COMMITTED, ['create' => 'token_list']);
    }

    public function CreateIndex()
    {
        Logger::EchoLog('Create Index');

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'token',
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_asc'],
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc'],
                ['key' => ['address' => 1, 'token_name' => 1], 'name' => 'address_token_name_asc', 'unique' => 1],
            ]
        ]);

        $this->db->Command(MongoDbConfig::DB_COMMITTED, [
            'createIndexes' => 'token_list',
            'indexes' => [
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc', 'unique' => 1],
            ]
        ]);
    }
}
