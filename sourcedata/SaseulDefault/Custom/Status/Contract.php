<?php

namespace Saseul\Custom\Status;

use Saseul\Common\Status;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;
use Saseul\Util\Parser;

class Contract extends Status
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

    public static function _Reset()
    {
        self::$cids = [];
        self::$contracts = [];
        self::$burn_cids = [];
    }

    public static function _Load()
    {
        self::$cids = array_values(array_unique(self::$cids));

        if (count(self::$cids) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['cid' => ['$in' => self::$cids]];
        $rs = $db->Query(MongoDb::NAMESPACE_CONTRACT, $filter);

        foreach ($rs as $item) {
            if (isset($item->contract)) {
                self::$contracts[$item->cid] = Parser::objectToArray($item->contract);
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$contracts as $k => $v) {
            $filter = ['cid' => $k];
            $row = [
                '$set' => [
                    'contract' => $v,
                    'status' => 'active',
                ],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        foreach (self::$burn_cids as $cid) {
            $filter = ['cid' => $cid];
            $row = [
                '$set' => [
                    'status' => 'burn',
                ],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDb::NAMESPACE_CONTRACT);
        }

        self::_Reset();
    }
}
