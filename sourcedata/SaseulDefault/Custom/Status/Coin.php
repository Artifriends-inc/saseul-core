<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;

class Coin implements Status
{
    protected static $addresses = [];
    protected static $balances = [];
    protected static $deposits = [];

    public static function LoadBalance($address)
    {
        self::$addresses[] = $address;
    }

    public static function LoadDeposit($address)
    {
        self::$addresses[] = $address;
    }

    public static function GetBalance($address)
    {
        if (isset(self::$balances[$address])) {
            return self::$balances[$address];
        }

        return 0;
    }

    public static function GetDeposit($address)
    {
        if (isset(self::$deposits[$address])) {
            return self::$deposits[$address];
        }

        return 0;
    }

    public static function SetBalance($address, int $value)
    {
        self::$balances[$address] = $value;
    }

    public static function SetDeposit($address, int $value)
    {
        self::$deposits[$address] = $value;
    }

    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void
    {
        self::$addresses = [];
        self::$balances = [];
        self::$deposits = [];
    }

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     */
    public static function _load(): void
    {
        self::$addresses = array_values(array_unique(self::$addresses));

        if (count(self::$addresses) === 0) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['address' => ['$in' => self::$addresses]];
        $rs = $db->Query(MongoDb::NAMESPACE_COIN, $filter);

        foreach ($rs as $item) {
            if (isset($item->balance)) {
                self::$balances[$item->address] = $item->balance;
            }

            if (isset($item->deposit)) {
                self::$deposits[$item->address] = $item->deposit;
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
     * Coin 값을 DB에 저장한다.
     *
     * @throws Exception
     */
    public static function _save(): void
    {
        $db = Database::getInstance();

        $balanceOperation = static::upsertCoinDB('balance', self::$balances);
        $depositOperation = static::upsertCoinDB('deposit', self::$deposits);

        $operations = array_merge($balanceOperation, $depositOperation);

        if (empty($operations)) {
            return;
        }

        $db->getCoinCollection()->bulkWrite($operations);

        self::_reset();
    }

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void
    {
    }

    private static function upsertCoinDB(string $type, array $memData): array
    {
        $operations = [];

        foreach ($memData as $key => $value) {
            $operations[] = [
                'updateOne' => [
                    ['address' => $key],
                    ['$set' => [$type => $value]],
                    ['upsert' => true],
                ]
            ];
        }

        return $operations;
    }
}
