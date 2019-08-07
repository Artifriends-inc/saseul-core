<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Block;
use Saseul\Core\Chunk;

class BunchInfo extends Api
{
    private $block_number;

    public function _init()
    {
        $this->block_number = $this->getParam($_REQUEST, 'block_number');
    }

    public function _process()
    {
        $fileExists = is_file(Chunk::txArchive($this->block_number));
        $bunchFinalNumber = Block::bunchFinalNumber($this->block_number);
        $block = Block::GetBlockByNumber($this->block_number);
        $finalBlock = Block::GetBlockByNumber($bunchFinalNumber);

        $this->data = [
            'file_exists' => $fileExists,
            'blockhash' => $block['blockhash'],
            'final_blockhash' => $finalBlock['blockhash'],
        ];
    }
}
