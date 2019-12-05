<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Block;

class BlockInfo extends Api
{
    private $block_number;

    public function _init()
    {
        $this->block_number = $this->getParam($_REQUEST, 'block_number');
    }

    public function _process()
    {
        $block = Block::getBlockInfoByNumber($this->block_number);
        $lastBlock = Block::getLastBlock();

        $this->data = [
            'target_block' => $block,
            'last_block' => $lastBlock,
            'last_blockhash' => $block['last_blockhash'],
            'blockhash' => $block['blockhash'],
            's_timestamp' => $block['s_timestamp'],
        ];
    }
}
