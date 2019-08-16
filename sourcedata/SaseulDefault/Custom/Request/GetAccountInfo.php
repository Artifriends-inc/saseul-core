<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\Attributes;
use Saseul\Custom\Method\Coin;
use Saseul\Custom\Method\Token;

class GetAccountInfo extends AbstractRequest
{
    public function getResponse(): array
    {
        $from = $this->request['from'];
        $all = Coin::GetAll([$from]);
        $balance = $all[$from]['balance'];
        $deposit = $all[$from]['deposit'];
        $token = Token::GetAll([$from]);
        $token = $token[$from];

        return [
            'coin' => [
                'balance' => $balance,
                'deposit' => $deposit,
            ],
            'role' => Attributes::GetRole($from),
            'token' => $token,
        ];
    }
}
