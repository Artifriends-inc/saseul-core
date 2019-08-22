<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Core\Schema;
use Saseul\System\Database;

/**
 * Class InitDatabase.
 */
class InitDatabase extends AbstractResource
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * @codeCoverageIgnore
     */
    public function process(): void
    {
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
