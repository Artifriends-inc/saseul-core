<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\System\Database;
use Saseul\Util\Parser;

/**
 * Class Contract.
 *
 * 사용하지 않고 있다.
 */
class Contract implements Status
{
    protected static $cids = [];
    protected static $contracts = [];
    protected static $burn_cids = [];

    /**
     * 불러올 Contract를 저장한다.
     *
     * @param string $cid 불러올 contract id
     */
    public static function loadContract(string $cid): void
    {
        self::$cids[] = $cid;
    }

    /**
     * Contract id를 이용하여 contract 정보를 가져온다.
     *
     * @param string $cid 불러올 contract id
     *
     * @return null|string
     */
    public static function getContract(string $cid): ?string
    {
        return self::$contracts[$cid] ?? null;
    }

    /**
     * Contract를 설정한다.
     *
     * @param string $cid      Contract id
     * @param array  $contract Contract 내용
     */
    public static function setContract(string $cid, array $contract): void
    {
        self::$contracts[$cid] = $contract;
    }

    /**
     * Burn 할 Contract 를 설정한다.
     *
     * @param string $cid Burn 할 Contract id
     */
    public static function burnContract(string $cid): void
    {
        self::$burn_cids[] = $cid;
        unset(self::$contracts[$cid]);
    }

    /**
     * Contract id 만든다.
     *
     * @param array $contract    Contract 내용
     * @param int   $s_timestamp Standard timestamp
     *
     * @return string
     */
    public static function makeCID(array $contract, int $s_timestamp): string
    {
        $chash = hash('sha256', json_encode($contract, JSON_THROW_ON_ERROR, 512));

        return $chash . $s_timestamp;
    }

    /**
     * 불러올 cid 목록을 반환한다.
     *
     * @return array
     */
    public function getAllCidList(): array
    {
        return self::$cids;
    }

    /**
     * 불로왔거나 추가한 Contract 목록을 반환한다.
     *
     * @return array
     */
    public function getAllContractList(): array
    {
        return self::$contracts;
    }

    /**
     * Burn 할 Contract 목록을 반환한다.
     *
     * @return array
     */
    public function getAllBurnCidList(): array
    {
        return self::$burn_cids;
    }

    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void
    {
        self::$cids = [];
        self::$contracts = [];
        self::$burn_cids = [];
    }

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     */
    public static function _load(): void
    {
        self::$cids = array_values(array_unique(self::$cids));

        if (empty(self::$cids)) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['cid' => ['$in' => self::$cids]];
        $cursor = $db->getContractCollection()->find($filter);

        foreach ($cursor as $item) {
            if (isset($item->contract)) {
                self::$contracts[$item->cid] = Parser::objectToArray($item->contract);
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
     * Contract 정보를 저장한다.
     *
     * @throws Exception
     */
    public static function _save(): void
    {
        $db = Database::getInstance();

        $operations = [];
        foreach (self::$contracts as $key => $value) {
            $operations[] = [
                'updateOne' => [
                    ['cid' => $key],
                    ['$set' => [
                        'contract' => $value,
                        'status' => 'active'
                    ]],
                    ['upsert' => true],
                ]
            ];
        }

        foreach (self::$burn_cids as $cid) {
            $operations[] = [
                'updateOne' => [
                    ['cid' => $cid],
                    ['$set' => ['status' => 'burn']],
                    ['upsert' => true],
                ]
            ];
        }

        if (empty($operations)) {
            return;
        }

        $db->getContractCollection()->bulkWrite($operations);

        self::_reset();
    }

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void
    {
    }
}
