<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Common\Schema;
use Saseul\System\Database;

/**
 * Class InitDatabase.
 */
class InitDatabase extends AbstractResource
{
    private $db;

    public function __construct()
    {
        $this->db = Database::GetInstance();
    }

    /**
     * @codeCoverageIgnore
     */
    public function process(): void
    {
        Schema::dropDatabaseOnMongoDB($this->db);
        Schema::createDatabaseOnMongoDB($this->db);
        Schema::createIndexOnMongoDB($this->db);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getResponse(): array
    {
        return ['status' => 'success'];
    }
}
