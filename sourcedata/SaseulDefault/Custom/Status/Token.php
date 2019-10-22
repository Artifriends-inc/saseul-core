<?php

namespace Saseul\Custom\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;

class Token extends Status
{
    protected static $addresses = [];
    protected static $token_names = [];
    protected static $balances = [];

    public static function LoadToken($address, $token_name)
    {
        self::$addresses[] = $address;
        self::$token_names[] = $token_name;
    }

    public static function GetBalance($address, $token_name)
    {
        if (isset(self::$balances[$address][$token_name])) {
            return self::$balances[$address][$token_name];
        }

        return 0;
    }

    public static function SetBalance($address, $token_name, int $value)
    {
        self::$balances[$address][$token_name] = (int) $value;
    }

    public static function _Reset()
    {
        self::$addresses = [];
        self::$token_names = [];
        self::$balances = [];
    }

    public static function _Load()
    {
        self::$addresses = array_values(array_unique(self::$addresses));
        self::$token_names = array_values(array_unique(self::$token_names));

        if (count(self::$addresses) === 0) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['address' => ['$in' => self::$addresses], 'token_name' => ['$in' => self::$token_names]];
        $rs = $db->Query(MongoDb::NAMESPACE_TOKEN, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                self::$balances[$item->address][$item->token_name] = $item->balance;
            }
        }
    }

    /**
     * Token 정보를 저장한다.
     *
     * @throws \Exception
     */
    public static function _Save()
    {
        $db = Database::getInstance();

        $operations = [];
        foreach (self::$balances as $address => $tokenList) {
            foreach ($tokenList as $tokenName => $balance) {
                $operations[] = [
                    'updateOne' => [
                        ['address' => $address, 'token_name' => $tokenName],
                        ['$set' => ['balance' => $balance]],
                        ['upsert' => true],
                    ]
                ];
            }
        }
        $db->getTokenCollection()->bulkWrite($operations);

        self::_Reset();
    }
}
