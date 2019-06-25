<?php

namespace Saseul\Custom\Status;

use Saseul\System\Database;
use Saseul\Common\Status;

class AuthTokenInfo extends Status
{
    protected static $namespace = 'saseul_committed.auth_token_info';

    protected static $codes = [];
    protected static $infos = [];

    public static function LoadInfo($code)
    {
        self::$codes[] = $code;
    }

    public static function GetInfo($code)
    {
        if (isset(self::$infos[$code])) {
            return self::$infos[$code];
        }

        return [];
    }

    public static function SetInfo($code, array $value)
    {
        self::$infos[$code] = $value;
    }

    public static function _Reset()
    {
        self::$codes = [];
        self::$infos = [];
    }

    public static function _Load()
    {
        self::$codes = array_values(array_unique(self::$codes));

        if (count(self::$codes) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['code' => ['$in' => self::$codes]];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->info)) {
                self::$infos[$item->code] = $item->info;
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$infos as $k => $v) {
            $filter = ['code' => $k];
            $row = [
                '$set' => ['info' => $v],
            ];
            $opt = ['upsert' => true];
            $db->bulk->update($filter, $row, $opt);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(self::$namespace);
        }

        self::_Reset();
    }
}
