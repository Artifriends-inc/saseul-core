<?php

namespace Saseul\Script\Patch;

use Saseul\Common\Script;
use Saseul\System\Database;
use Saseul\Util\Mongo;

class Token extends Script
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function _process()
    {
        $this->CreateDatabase();
        $this->CreateIndex();
    }

    public function CreateDatabase()
    {
        static::log()->info('Create database');

        $this->db->Command(Mongo::DB_COMMITTED, ['create' => Mongo::COLLECTION_TOKEN]);
        $this->db->Command(Mongo::DB_COMMITTED, ['create' => Mongo::COLLECTION_TOKEN_LIST]);
    }

    public function CreateIndex()
    {
        static::log()->info('Create Index');

        $this->db->Command(Mongo::DB_COMMITTED, [
            'createIndexes' => Mongo::COLLECTION_TOKEN,
            'indexes' => [
                ['key' => ['address' => 1], 'name' => 'address_asc'],
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc'],
                ['key' => ['address' => 1, 'token_name' => 1], 'name' => 'address_token_name_asc', 'unique' => 1],
            ]
        ]);

        $this->db->Command(Mongo::DB_COMMITTED, [
            'createIndexes' => Mongo::COLLECTION_TOKEN_LIST,
            'indexes' => [
                ['key' => ['token_name' => 1], 'name' => 'token_name_asc', 'unique' => 1],
            ]
        ]);
    }
}
