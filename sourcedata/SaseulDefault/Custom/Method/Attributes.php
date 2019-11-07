<?php

namespace Saseul\Custom\Method;

use Saseul\Constant\MongoDb;
use Saseul\Constant\Role;
use Saseul\System\Database;

/**
 * Class Attributes.
 */
class Attributes
{
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

    public static function GetFullNode($query = ['key' => 'role', 'value' => ['$in' => Role::FULL_NODES]])
    {
        $db = Database::getInstance();
        $rs = $db->Query(MongoDb::NAMESPACE_ATTRIBUTE, $query);
        $nodes = [];

        foreach ($rs as $item) {
            if (isset($item->address)) {
                $nodes[] = $item->address;
            }
        }

        return $nodes;
    }

    public static function GetValidator()
    {
        return self::GetFullNode(['key' => 'role', 'value' => Role::VALIDATOR]);
    }

    public static function GetSupervisor()
    {
        return self::GetFullNode(['key' => 'role', 'value' => Role::SUPERVISOR]);
    }

    public static function GetArbiter()
    {
        return self::GetFullNode(['key' => 'role', 'value' => Role::ARBITER]);
    }
}
