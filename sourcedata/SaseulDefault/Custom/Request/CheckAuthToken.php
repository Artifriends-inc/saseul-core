<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\AuthToken;

// TODO: 1.0 버전에는 제공되지 않을 API, 추후 제거
class CheckAuthToken extends AbstractRequest
{
    private $authkey;

    public function initialize(
        array $request,
        string $thash,
        string $public_key,
        string $signature
    ): void {
        parent::initialize($request, $thash, $public_key, $signature);
        $this->authkey = $request['authkey'] ?? '';
    }

    public function getResponse(): array
    {
        return AuthToken::CheckToken($this->authkey);
    }
}
