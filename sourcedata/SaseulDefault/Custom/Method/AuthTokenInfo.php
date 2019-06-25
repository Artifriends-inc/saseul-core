<?php

namespace Saseul\Custom\Method;

use Saseul\System\Database;
use Saseul\Util\Parser;

class AuthTokenInfo
{
    public static function GetInfo($code)
    {
        $db = Database::GetInstance();

        $filter = ['code' => $code];

        $rs = $db->Query('saseul_committed.auth_token_info', $filter);
        $info = [];

        foreach ($rs as $item) {
            if (isset($item->info)) {
                $info = Parser::objectToArray($item->info);
            }
        }

        return $info;
    }
}
