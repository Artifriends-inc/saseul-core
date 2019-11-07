<?php

namespace Saseul\Custom\Request;

use MongoDB\Driver\Exception\Exception;
use Saseul\Custom\Method\Token;

/**
 * Class GetTokenBalance
 * 요청한 Account 가 가진 Token 모든 토큰 Balance를 제공한다.
 */
class GetTokenBalance extends AbstractRequest
{
    /**
     * @throws Exception
     *
     * @return array
     */
    public function getResponse(): array
    {
        $from = $this->from;
        $all = Token::GetAll([$from]);

        return $all[$from];
    }
}
