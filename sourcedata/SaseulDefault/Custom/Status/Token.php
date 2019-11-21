<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;

class Token implements Status
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

    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void
    {
        self::$addresses = [];
        self::$token_names = [];
        self::$balances = [];
    }

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public static function _load(): void
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
     * Status 값을 전처리한다.
     */
    public static function _preprocess(): void
    {
    }

    /**
     * Status 정보를 저장한다.
     *
     * @throws Exception
     */
    public static function _save(): void
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

        if (empty($operations)) {
            return;
        }

        $db->getTokenCollection()->bulkWrite($operations);

        self::_reset();
    }

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void
    {
    }
}
