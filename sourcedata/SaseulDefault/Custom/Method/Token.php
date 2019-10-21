<?php

namespace Saseul\Custom\Method;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;

class Token
{
    public static function GetAll($addresses, $token_names = null)
    {
        $db = Database::getInstance();

        $all = [];

        foreach ($addresses as $address) {
            $all[$address] = [];
        }

        $filter = ['address' => ['$in' => $addresses]];

        if ($token_names !== null) {
            $filter = [
                'address' => ['$in' => $addresses],
                'token_name' => ['$in' => $token_names],
            ];
        }

        $rs = $db->Query(MongoDb::NAMESPACE_TOKEN, $filter);

        foreach ($rs as $item) {
            $all[$item->address][] = [
                'name' => $item->token_name,
                'balance' => (int) $item->balance
            ];
        }

        return $all;
    }
}
