<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\Role;
use Saseul\System\Database;

class Attributes implements Status
{
    protected static $addresses_role = [];
    protected static $roles = [];

    /**
     * 주송 해당되는 role을 불러오기위해서 저장한다.
     *
     * @param string $address Account address
     */
    public static function loadRole(string $address): void
    {
        self::$addresses_role[] = $address;
    }

    /**
     * 해당 Account address로 role을 정의한다.
     *
     * @param string $address Account address
     *
     * @return string
     */
    public static function getRole(string $address): string
    {
        return self::$roles[$address] ?? Role::LIGHT;
    }

    /**
     * 해당 Account address 에게 role을 부여합니다.
     *
     * @param string $address Account address
     * @param string $value   Role
     */
    public static function setRole(string $address, string $value): void
    {
        self::$roles[$address] = $value;
    }

    /**
     * 불러올 account address 목록을 반환합니다.
     *
     * @return array
     */
    public function getAllAddressList(): array
    {
        return self::$addresses_role;
    }

    /**
     * 불러온 account role 목록를 반환합니다.
     *
     * @return array
     */
    public function getAllRoleList(): array
    {
        return self::$roles;
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
     */
    public static function _load(): void
    {
        self::$addresses_role = array_values(array_unique(self::$addresses_role));

        if (empty(self::$addresses_role)) {
            return;
        }

        $db = Database::getInstance();
        $filter = [
            'address' => ['$in' => self::$addresses_role],
            'key' => 'role'
        ];
        $cursor = $db->getAttributesCollection()->find($filter);

        foreach ($cursor as $item) {
            self::$roles[$item->address] = $item->value ?? Role::LIGHT;
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
