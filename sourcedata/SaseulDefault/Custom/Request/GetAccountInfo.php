<?php

namespace Saseul\Custom\Request;

use MongoDB\Driver\Exception\Exception;
use Saseul\Custom\Method\Attributes;
use Saseul\Custom\Method\Coin;
use Saseul\Custom\Method\Token;

/**
 * Class GetAccountInfo.
 * Account 정보를 가져온다.
 */
class GetAccountInfo extends AbstractRequest
{
    /**
     * @throws Exception
     *
     * @return array (See below)
     */
    public function getResponse(): array
    {
        $from = $this->from;
        $all = Coin::getAll([$from]);
        $balance = $all[$from]['balance'];
        $deposit = $all[$from]['deposit'];

        $token = Token::getAll([$from]);
        $token = $token[$from];

        return [
            'coin' => [
                'balance' => $balance,
                'deposit' => $deposit,
            ],
            'role' => Attributes::getRole($from),
            'token' => $token,
        ];
    }
}
