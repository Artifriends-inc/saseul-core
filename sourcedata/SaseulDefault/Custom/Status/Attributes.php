<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Role;
use Saseul\System\Database;

class Attributes implements Status
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

    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void
    {
        self::$addresses_role = [];
        self::$roles = [];
    }

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function _load(): void
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
     * Status 값을 전처리한다.
     */
    public static function _preprocess(): void
    {
    }

    /**
     * Status 값을 저장한다.
     *
     * @throws Exception
     */
    public static function _save(): void
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

        if (empty($operations)) {
            return;
        }

        $db->getAttributesCollection()->bulkWrite($operations);

        self::_reset();
    }

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void
    {
    }
}
