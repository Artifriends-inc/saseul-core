<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\NodeInfo;
use Saseul\System\Key;

class Sign extends Api
{
    private $string;

    public function _init()
    {
        $this->string = $this->getParam($_REQUEST, 'string', ['default' => '']);
    }

    public function _process()
    {
        if (!is_string($this->string) || mb_strlen($this->string) !== 32) {
            return;
        }

        $privateKey = NodeInfo::getPrivateKey();
        $publicKey = NodeInfo::getPublicKey();
        $address = NodeInfo::getAddress();
        $signature = Key::makeSignature($this->string, $privateKey, $publicKey);

        $this->data = [
            'string' => $this->string,
            'public_key' => $publicKey,
            'address' => $address,
            'signature' => $signature,
        ];
    }
}
