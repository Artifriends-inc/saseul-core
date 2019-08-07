<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Block;
use Saseul\Core\Chunk;

class RemoveOldBlock extends Script
{
    public function _process()
    {
        $lastBlock = Block::lastBlock();
        $lastBlockNumber = $lastBlock['block_number'];

        Chunk::removeOldBlock($lastBlockNumber);
    }

    public function GetNextIP(string $ip): string
    {
        $ip_min = 1;
        $ip_max = 255;

        $ip_array = explode('.', $ip);

        if ((int) $ip_array[3] < $ip_max) {
            $ip_array[3] = (int) $ip_array[3] + 1;
        } else {
            if ((int) $ip_array[2] < $ip_max) {
                $ip_array[2] = (int) $ip_array[2] + 1;
                $ip_array[3] = $ip_min;
            } else {
                if ((int) $ip_array[1] < $ip_max) {
                    $ip_array[1] = (int) $ip_array[1] + 1;
                    $ip_array[2] = $ip_min;
                    $ip_array[3] = $ip_min;
                } else {
                    $ip_array[0] = (int) $ip_array[0] + 1;
                    $ip_array[1] = $ip_min;
                    $ip_array[2] = $ip_min;
                    $ip_array[3] = $ip_min;
                }
            }
        }

        return implode('.', $ip_array);
    }
}
