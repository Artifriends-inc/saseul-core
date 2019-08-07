<?php

namespace Saseul\Custom\Status;

use Saseul\Common\Status;
use Saseul\System\Database;

class AuthToken extends Status
{
    protected static $namespace = 'saseul_committed.auth_token';

    protected static $tids = [];
    protected static $values = [];

    public static function LoadToken($tid)
    {
        self::$tids[] = $tid;
    }

    public static function GetValue($tid)
    {
        if (isset(self::$values[$tid])) {
            return self::$values[$tid];
        }

        return [];
    }

    public static function SetValue($tid, array $value)
    {
        self::$values[$tid] = $value;
    }

    public static function _Reset()
    {
        self::$tids = [];
        self::$values = [];
    }

    public static function _Load()
    {
        self::$tids = array_values(array_unique(self::$tids));

        if (count(self::$tids) === 0) {
            return;
        }

        $db = Database::GetInstance();
        $filter = ['tid' => ['$in' => self::$tids]];
        $rs = $db->Query(self::$namespace, $filter);

        foreach ($rs as $item) {
            if (isset($item->tid)) {
                self::$values[$item->tid] = [];

                if (isset($item->owner)) {
                    self::$values[$item->tid]['owner'] = $item->owner;
                }
                if (isset($item->code)) {
                    self::$values[$item->tid]['code'] = $item->code;
                }
                if (isset($item->auth_code)) {
                    self::$values[$item->tid]['auth_code'] = $item->auth_code;
                }
                if (isset($item->status)) {
                    self::$values[$item->tid]['status'] = $item->status;
                }
            }
        }
    }

    public static function _Save()
    {
        $db = Database::GetInstance();

        foreach (self::$values as $k => $v) {
            $filter = ['tid' => $k];
            $row = [
                '$set' => [
                    'owner' => $v['owner'],
                    'code' => $v['code'],
                    'auth_code' => $v['auth_code'],
                    'status' => $v['status'],
                ],
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
