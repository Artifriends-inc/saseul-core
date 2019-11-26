<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\System\Database;

class Token implements Status
{
    protected static $addresses = [];
    protected static $token_names = [];
    protected static $balances = [];

    /**
     * 읽어올 Token 들을 정의한다.
     *
     * @param string $address    Account Address
     * @param string $token_name Token 이름
     */
    public static function loadToken(string $address, string $token_name)
    {
        self::$addresses[] = $address;
        self::$token_names[] = $token_name;
    }

    /**
     * Balance 정보를 가져온다.
     *
     * @param string $address    Account Address
     * @param string $token_name Token 이름
     *
     * @return int 데이터가 없으면 0으로 반환된다.
     */
    public static function getBalance(string $address, string $token_name): int
    {
        return self::$balances[$address][$token_name] ?? 0;
    }

    /**
     * Token balance 를 저장한다.
     *
     * @param string $address    Token 소유자 주소
     * @param string $token_name Token 이름
     * @param int    $value      Token 수량
     */
    public static function setBalance(string $address, string $token_name, int $value): void
    {
        self::$balances[$address][$token_name] = $value;
    }

    /**
     * 읽어올 주소를 반환한다.
     *
     * @return array
     */
    public function getAllAddressList(): array
    {
        return self::$addresses;
    }

    /**
     * 읽어올 Token 이름을 반환한다.
     *
     * @return array
     */
    public function getAllTokenNameList(): array
    {
        return self::$token_names;
    }

    /**
     * 쓰거나 읽어온 Token에 대한 balance를 반환한다.
     *
     * @return array
     */
    public function getAllTokenBalance(): array
    {
        return self::$balances;
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
     */
    public static function _load(): void
    {
        self::$addresses = array_values(array_unique(self::$addresses));
        self::$token_names = array_values(array_unique(self::$token_names));

        if (empty(self::$addresses)) {
            return;
        }

        $db = Database::getInstance();
        $filter = [
            'address' => ['$in' => self::$addresses],
            'token_name' => ['$in' => self::$token_names]
        ];
        $cursor = $db->getTokenCollection()->find($filter);

        foreach ($cursor as $item) {
            self::$balances[$item->address][$item->token_name] = $item->balance ?? 0;
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
