<?php

namespace Saseul\Custom\Request;

use MongoDB\Driver\Exception\Exception;
use Saseul\Custom\Method\Coin;

/**
 * Class GetBalance.
 * Coin Balance 를 가져온다.
 */
class GetBalance extends AbstractRequest
{
    /**
     * @throws Exception
     *
     * @return array (See below)
     */
    public function getResponse(): array
    {
        $from = $this->from;
        $all = Coin::GetAll([$from]);
        $balance = $all[$from]['balance'];
        $deposit = $all[$from]['deposit'];

        return [
            'balance' => $balance,
            'deposit' => $deposit,
        ];
    }
}
