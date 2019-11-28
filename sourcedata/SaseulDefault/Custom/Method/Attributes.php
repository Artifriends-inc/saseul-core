<?php

namespace Saseul\Custom\Method;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Role;
use Saseul\System\Database;

/**
 * Class Attributes.
 */
class Attributes
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function GetRole($address)
    {
        $db = Database::getInstance();
        $query = ['address' => $address, 'key' => 'role'];
        $rs = $db->Query(MongoDb::NAMESPACE_ATTRIBUTE, $query);
        $node = [
            'address' => $address,
            'role' => Role::LIGHT,
        ];

        foreach ($rs as $item) {
            if (isset($item->address)) {
                $node['role'] = $item->value;
            }
        }

        return $node;
    }

    /**
     * Attribute DB에 저장되어있는 Validator, Supervisor, Arbiter 들의 address를 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getFullNode(): array
    {
        return (new self())->getAddressList(Role::FULL_NODES);
    }

    /**
     * Attribute DB에 저장되어있는 Validator 들의 address를 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getValidator(): array
    {
        return (new self())->getAddressList([Role::VALIDATOR]);
    }

    /**
     * Attribute DB에 저장되어있는 Supervisor 들의 address를 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getSupervisor(): array
    {
        return (new self())->getAddressList([Role::SUPERVISOR]);
    }

    /**
     * Attribute DB에 저장되어있는 Arbiter 들의 address를 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getArbiter(): array
    {
        return (new self())->getAddressList([Role::ARBITER]);
    }

    /**
     * @param array $valueList
     *
     * @throws Exception
     *
     * @return array
     */
    private function getAddressList(array $valueList): array
    {
        $filter = [
            'key' => 'role',
            'value' => ['$in' => $valueList],
        ];
        $cursor = $this->db->getAttributesCollection()->find($filter);

        $nodeAddressList = [];
        foreach ($cursor as $item) {
            $nodeAddressList[] = $item->address;
        }

        return $nodeAddressList;
    }
}
