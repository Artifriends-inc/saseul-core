<?php

namespace Saseul\Custom\Method;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;

class Token
{
    public static function GetAll($addresses, $token_names = null)
    {
        $db = Database::GetInstance();

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

    public static function SetAll($all)
    {
        $db = Database::GetInstance();

        foreach ($all as $address => $item) {
            foreach ($item as $token_name => $balance) {
                $filter = ['address' => $address, 'token_name' => $token_name];
                $row = [
                    '$set' => [
                        'balance' => $balance,
                    ],
                ];
                $opt = ['upsert' => true];
                $db->bulk->update($filter, $row, $opt);
            }
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDb::NAMESPACE_TOKEN);
        }
    }
}
