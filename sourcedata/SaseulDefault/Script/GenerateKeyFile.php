<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\NodeInfo;

class GenerateKeyFile extends Script
{
    public function _process()
    {
        $filename = SASEUL_DIR . '/' . NodeInfo::getAddress() . '.keyfile';

        $contents = [
            'private_key' => NodeInfo::getPrivateKey(),
            'public_key' => NodeInfo::getPublicKey(),
            'address' => NodeInfo::getAddress(),
        ];

        $contents = json_encode($contents);

        file_put_contents($filename, $contents);
    }
}
