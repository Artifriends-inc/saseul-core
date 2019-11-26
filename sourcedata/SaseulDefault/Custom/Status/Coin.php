<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\System\Database;

class Coin implements Status
{
    protected static $addresses = [];
    protected static $balances = [];
    protected static $deposits = [];

    /**
     * Coin 정보를 불러올 Address를 추가한다.
     *
     * @param string $address Account Address
     */
    public static function loadCoin(string $address): void
    {
        self::$addresses[] = $address;
    }

    /**
     * @param string $address
     *
     * @deprecated loadCoin 으로 변경한다.
     *
     * @todo SC-282
     */
    public static function loadBalance(string $address): void
    {
        self::$addresses[] = $address;
    }

    /**
     * @param string $address
     *
     * @deprecated loadCoin 으로 변경한다.
     *
     * @todo SC-282
     */
    public static function loadDeposit(string $address): void
    {
        self::$addresses[] = $address;
    }

    /**
     * 불러온 address 에 대한 Coin balance 정보를 반환한다.
     *
     * @param string $address Account address
     *
     * @return int
     */
    public static function getBalance(string $address): int
    {
        return self::$balances[$address] ?? 0;
    }

    /**
     * 불러온 address 에 대한 Coin deposit 정보를 반환한다.
     *
     * @param string $address Account address
     *
     * @return int
     */
    public static function getDeposit(string $address): int
    {
        return self::$deposits[$address] ?? 0;
    }

    /**
     * Balance 정보를 설정한다.
     *
     * @param string $address Account address
     * @param int    $value   Account coin balance
     */
    public static function setBalance(string $address, int $value): void
    {
        self::$balances[$address] = $value;
    }

    /**
     * Deposit 정보를 설정한다.
     *
     * @param string $address Account address
     * @param int    $value   Account coin deposit
     */
    public static function setDeposit(string $address, int $value): void
    {
        self::$deposits[$address] = $value;
    }

    /**
     * 불러오거나 저장될 Account address 전체 목록을 반환한다.
     *
     * @return array
     */
    public function getAllAddressList(): array
    {
        return self::$addresses;
    }

    /**
     * 불러오거나 저장될 Account 별 coin balance 전체 목록을 반환한다.
     *
     * @return array
     */
    public function getAllBalanceList(): array
    {
        return self::$balances;
    }

    /**
     * 불러오거나 저장될 Account 별 coin deposit 전체 목록을 반환한다.
     */
    public function getAllDepositList(): array
    {
        return self::$deposits;
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

        if (empty(self::$addresses)) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['address' => ['$in' => self::$addresses]];
        $cursor = $db->getCoinCollection()->find($filter);

        foreach ($cursor as $item) {
            self::$balances[$item->address] = $item->balance ?? 0;
            self::$deposits[$item->address] = $item->deposit ?? 0;
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
