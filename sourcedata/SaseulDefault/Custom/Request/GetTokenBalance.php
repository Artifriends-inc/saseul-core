<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\Token;

class GetTokenBalance extends AbstractRequest
{
    public function getResponse(): array
    {
        $from = $this->request['from'];
        $all = Token::GetAll([$from]);

        return $all[$from];
    }
}
