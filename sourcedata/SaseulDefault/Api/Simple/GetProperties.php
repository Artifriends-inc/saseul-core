<?php

namespace Saseul\Api\Simple;

use Saseul\Common\Api;
use Saseul\Core\Block;
use Saseul\Core\Property;

class GetProperties extends Api
{
    public function _process()
    {
        $all = Property::getAll();
        $lastBlock = Block::GetLastBlock();
        $lastRoundKey = hash('ripemd160', $lastBlock['last_blockhash']) . $lastBlock['block_number'];

        $all['lastBlock'] = $lastBlock;
        $all['lastHashInfo'] = Property::hashInfo($lastRoundKey);

        $this->data = $all;
    }
}
