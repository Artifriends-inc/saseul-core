<?php

namespace Saseul\Custom\Method;

use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\Parser;

class AuthToken
{
    public static function GetAll($address)
    {
        $db = Database::GetInstance();

        $filter = ['owner' => $address];
        $opt = ['sort' => ['_id' => -1]];

        $rs = $db->Query('saseul_committed.auth_token', $filter, $opt);
        $tokens = [];

        $max = 10;
        $count = 0;

        foreach ($rs as $item) {
            $token = Parser::objectToArray($item);
            unset($token['_id']);

            $tokens[] = $token;

            $count = $count + 1;

            if ($count >= $max) {
                break;
            }
        }

        return $tokens;
    }

    public static function CheckToken($authkey) {
        $db = Database::GetInstance();
        $token = [];

        $var_pos = mb_strrpos($authkey, "_");

        $code = mb_substr($authkey, 0, $var_pos);
        $auth_prk = mb_substr($authkey, $var_pos + 1);

        if (mb_strlen($auth_prk) === 64) {
            $auth_code = Key::makePublicKey($auth_prk);

            $tid = $code . "_" . $auth_code;

            $filter = ['tid' => $tid];

            $rs = $db->Query('saseul_committed.auth_token', $filter);

            foreach ($rs as $item) {
                $token = Parser::objectToArray($item);
                unset($token['_id']);
            }
        }

        return $token;
    }
}
