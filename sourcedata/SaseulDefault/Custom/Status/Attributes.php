<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Role;
use Saseul\System\Database;

class Attributes extends Status
{
    protected static $addresses_role = [];
    protected static $roles = [];

    public static function LoadRole($address)
    {
        self::$addresses_role[] = $address;
    }

    public static function GetRole($address)
    {
        if (isset(self::$roles[$address])) {
            return self::$roles[$address];
        }

        return Role::LIGHT;
    }

    public static function SetRole($address, string $value)
    {
        self::$roles[$address] = $value;
    }

    public static function _Reset()
    {
        self::$addresses_role = [];
        self::$roles = [];
    }

    public static function _Load()
    {
        self::$addresses_role = array_values(array_unique(self::$addresses_role));

        if (count(self::$addresses_role) === 0) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['address' => ['$in' => self::$addresses_role], 'key' => 'role'];
        $rs = $db->Query(MongoDb::NAMESPACE_ATTRIBUTE, $filter);

        foreach ($rs as $item) {
            if (isset($item->value)) {
                self::$roles[$item->address] = $item->value;
            }
        }
    }

    /**
     * Memory 에 저장해둔 정보를 DB에 저장한다.
     *
     * @throws Exception
     */
    public static function _Save()
    {
        $db = Database::getInstance();

        $operations = [];
        foreach (self::$roles as $key => $value) {
            $operations[] = [
                'updateOne' => [
                    ['address' => $key, 'key' => 'role'],
                    ['$set' => ['key' => 'role', 'value' => $value]],
                    ['upsert' => true],
                ]
            ];
        }
        $db->getAttributesCollection()->bulkWrite($operations);

        self::_Reset();
    }
}
