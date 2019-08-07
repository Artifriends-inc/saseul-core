<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Block;
use Saseul\Core\Generation;
use Saseul\Util\TypeChecker;

class GenerationInfo extends Api
{
    private $block_number;

    public function _init()
    {
        $this->block_number = $this->getParam($_REQUEST, 'block_number');
    }

    public function _process()
    {
        $generationOriginNumber = Block::generationOriginNumber($this->block_number);
        $generation = Generation::generationByNumber($generationOriginNumber);

        if (!TypeChecker::StructureCheck(Structure::GENERATION, $generation)) {
            return;
        }

        $sourceHash = $generation['source_hash'];
        $fileExists = false;
        $sourceName = Directory::SOURCE . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";

        if (is_file($sourceName)) {
            $fileExists = true;
        }

        $this->data = [
            'file_exists' => $fileExists,
            'origin_blockhash' => $generation['origin_blockhash'],
            'final_blockhash' => $generation['final_blockhash'],
            'source_hash' => $generation['source_hash'],
            'source_version' => $generation['source_version'],
        ];
    }
}
