<?php

namespace Saseul\Custom\Method;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;

/**
 * Class Coin provides functions related to the coin used by the API.
 */
class Coin
{
    /**
     * Returns account information for multiple accounts.
     *
     * The account information currently returned are balance and deposit.
     *
     * @param array $addresses Addresses that look up account information.
     *
     * @return array Account information for balance and deposit held by each account.
     */
    public static function GetAll($addresses)
    {
        $db = Database::getInstance();

        $all = [];

        foreach ($addresses as $address) {
            $all[$address] = [
                'balance' => 0,
                'deposit' => 0,
            ];
        }

        $filter = ['address' => ['$in' => $addresses]];
        $rs = $db->Query(MongoDb::NAMESPACE_COIN, $filter);

        foreach ($rs as $item) {
            if (isset($item->address, $item->balance)) {
                $all[$item->address]['balance'] = (int) $item->balance;
            }

            if (isset($item->address, $item->deposit)) {
                $all[$item->address]['deposit'] = (int) $item->deposit;
            }
        }

        return $all;
    }
}
