<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Block;
use Saseul\Core\Generation;
use Saseul\Util\TypeChecker;

class Source extends Api
{
    private $block_number;

    public function _init()
    {
        $this->block_number = (int) $this->getParam($_REQUEST, 'block_number', ['default' => 0]);
    }

    public function _process()
    {
        $generationOriginNumber = Block::generationOriginNumber($this->block_number);
        $generation = Generation::generationByNumber($generationOriginNumber);

        if (!TypeChecker::StructureCheck(Structure::GENERATION, $generation)) {
            return;
        }

        $sourceHash = $generation['source_hash'];
        $sourceName = Directory::SOURCE . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";
        $this->findGz($sourceName);
    }

    public function findGz($sourceName)
    {
        if (is_file($sourceName)) {
            $fileSize = filesize($sourceName);
            $filename = pathinfo($sourceName)['basename'];

            header('Pragma: public');
            header('Expires: 0');
            header('Content-Type: application/octet-stream');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Content-Transfer-Encoding: binary');
            header("Content-Length: {$fileSize}");

            ob_clean();
            flush();
            readfile($sourceName);

            exit();
        }
    }
}
