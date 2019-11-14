<?php

namespace Saseul\Script\Patch;

use Saseul\Common\Script;
use Saseul\System\Database;
use Saseul\Util\Mongo;

class Contract extends Script
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

        $this->db->Command(Mongo::DB_COMMITTED, ['create' => Mongo::COLLECTION_CONTRACT]);
    }

    public function CreateIndex()
    {
        static::log()->info('Create index');

        $this->db->Command(Mongo::DB_COMMITTED, [
            'createIndexes' => Mongo::COLLECTION_CONTRACT,
            'indexes' => [
                ['key' => ['cid' => 1], 'name' => 'cid_asc'],
                ['key' => ['chash' => 1], 'name' => 'chash_asc'],
                ['key' => ['timestamp' => 1], 'name' => 'timestamp_asc'],
                ['key' => ['timestamp' => -1], 'name' => 'timestamp_desc'],
                ['key' => ['timestamp' => 1, 'chash' => 1], 'name' => 'timestamp_chash_asc'],
            ]
        ]);
    }
}
