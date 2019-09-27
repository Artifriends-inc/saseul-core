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
    private $status;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->status = 'success';
    }

    /**
     * @codeCoverageIgnore
     */
    public function process(): void
    {
        $isDropDatabase = Schema::dropDatabaseOnMongoDB($this->db);
        if (!$isDropDatabase) {
            $this->status = 'fail';
        }
        Schema::createIndexOnMongoDB($this->db);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getResponse(): array
    {
        return ['status' => $this->status];
    }
}
