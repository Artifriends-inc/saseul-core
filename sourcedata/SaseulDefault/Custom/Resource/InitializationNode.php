<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Common\Schema;
use Saseul\Constant\Role;
use Saseul\Core\Tracker;
use Saseul\System\Database;

/**
 * Class InitializationNode.
 *
 * Database를 초기화하고 Tracker 를 설정한다.
 */
class InitializationNode extends AbstractResource
{
    private $db;
    private $nodeRole;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->nodeRole = Role::LIGHT;
    }

    public function process(): void
    {
        Schema::dropDatabaseOnMongoDB($this->db);
        Schema::createIndexOnMongoDB($this->db);
        $this->nodeRole = Tracker::addTrackerOnDb();
    }

    /**
     * @return array 초기화 한 값을 반환한다.
     *               ['role'] - Node role
     */
    public function getResponse(): array
    {
        return [
            'role' => $this->nodeRole
        ];
    }
}
