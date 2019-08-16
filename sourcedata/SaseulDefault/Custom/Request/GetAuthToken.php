<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\AuthToken;
use Saseul\Custom\Method\AuthTokenInfo;

// TODO: 1.0 버전에는 제공되지 않을 기능으로 추후 제거
class GetAuthToken extends AbstractRequest
{
    public function getResponse(): array
    {
        $tmp_tokens = AuthToken::GetAll($this->from);
        $tokens = [];
        $tmp_item = [];

        foreach ($tmp_tokens as $item) {
            if (isset($item['code'])) {
                $info = AuthTokenInfo::GetInfo($item['code']);
                $tmp_item = $item;
                $tmp_item['info'] = $info;

                $tokens[] = $tmp_item;
            }
        }

        return $tokens;
    }
}
