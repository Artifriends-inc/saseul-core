<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;

// TODO: 1.0 버전에는 제공되지 않을 API, 추후 제거
class GetAllAuthTokenInfo extends AbstractRequest
{
    public function getResponse(): array
    {
//        $from = $this->request['from'];
//        $all = Coin::GetAll([$from]);
//        $balance = $all[$from]['balance'];
//        $deposit = $all[$from]['deposit'];
//        $token = Token::GetAll([$from]);
//        $token = $token[$from];

        // info

        return [];
    }
}
