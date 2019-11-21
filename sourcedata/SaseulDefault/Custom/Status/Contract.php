<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
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

    public static function LoadContract(string $cid)
    {
        self::$cids[] = $cid;
    }

    public static function GetContract(string $cid)
    {
        if (isset(self::$contracts[$cid])) {
            return self::$contracts[$cid];
        }

        return null;
    }

    public static function SetContract(string $cid, array $contract)
    {
        self::$contracts[$cid] = $contract;
    }

    public static function BurnContract(string $cid)
    {
        self::$burn_cids[] = $cid;
        unset(self::$contracts[$cid]);
    }

    public static function MakeCID(array $contract, int $s_timestamp)
    {
        $chash = hash('sha256', json_encode($contract));

        return $chash . $s_timestamp;
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

        if (count(self::$cids) === 0) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['cid' => ['$in' => self::$cids]];
        $rs = $db->Query(MongoDb::NAMESPACE_CONTRACT, $filter);

        foreach ($rs as $item) {
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
