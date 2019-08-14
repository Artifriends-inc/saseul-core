<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\Coin;

class GetBalance extends AbstractRequest
{
    public function getResponse(): array
    {
        $from = $this->request['from'];
        $all = Coin::GetAll([$from]);
        $balance = $all[$from]['balance'];
        $deposit = $all[$from]['deposit'];

        return [
            'balance' => $balance,
            'deposit' => $deposit,
        ];
    }
}
