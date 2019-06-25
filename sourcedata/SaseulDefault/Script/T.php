<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Block;
use Saseul\Core\Chunk;
use Saseul\Util\Logger;

class T extends Script
{
    public function _process()
    {
        $a = [
            [
                'host' => 'a',
                'address' => 'b',
            ],
            [
                'host' => 'a',
                'address' => 'b',
            ],
            [
                'host' => 'c',
                'address' => 'd',
            ],
        ];

        $b = [
            [
                'host' => 'c',
                'address' => 'd',
            ],
            [
                'host' => 'c',
                'address' => 'd',
            ],
        ];

        $c = [
            [
                'host' => 'c',
                'address' => 'd',
            ],
            [
                'host' => 'a',
                'address' => 'b',
            ],
        ];

        $d = array_merge($a, $b);
        $d = array_merge($c, $d);

        $d = array_unique(array_map(function ($obj) { return json_encode($obj); }, $d));
        $d = array_map(function ($obj) { return json_decode($obj, true); }, $d);

        Logger::Log($d);
    }

    public function GetNextIP(string $ip): string
    {
        $ip_min = 1;
        $ip_max = 255;

        $ip_array = explode(".", $ip);

        if ((int)$ip_array[3] < $ip_max) {
            $ip_array[3] = (int)$ip_array[3] + 1;
        } else {
            if ((int)$ip_array[2] < $ip_max) {
                $ip_array[2] = (int)$ip_array[2] + 1;
                $ip_array[3] = $ip_min;
            } else {
                if ((int)$ip_array[1] < $ip_max) {
                    $ip_array[1] = (int)$ip_array[1] + 1;
                    $ip_array[2] = $ip_min;
                    $ip_array[3] = $ip_min;
                } else {
                    $ip_array[0] = (int)$ip_array[0] + 1;
                    $ip_array[1] = $ip_min;
                    $ip_array[2] = $ip_min;
                    $ip_array[3] = $ip_min;
                }
            }
        }

        $return_ip = implode(".", $ip_array);

        return $return_ip;
    }
}
