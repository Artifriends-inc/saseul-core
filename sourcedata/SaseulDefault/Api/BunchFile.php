<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Chunk;

class BunchFile extends Api
{
    private $block_number;

    public function _init()
    {
        $this->block_number = (int) $this->getParam($_REQUEST, 'block_number', ['default' => 0]);
    }

    public function _process()
    {
        $txArchive = Chunk::txArchive($this->block_number);

        $this->findGz($txArchive);
    }

    public function findGz($txArchive)
    {
        if (is_file($txArchive)) {
            $fileSize = filesize($txArchive);
            $filename = pathinfo($txArchive)['basename'];

            header("Pragma: public");
            header("Expires: 0");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename={$filename}");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: {$fileSize}");

            ob_clean();
            flush();
            readfile($txArchive);

            exit();
        }
    }
}