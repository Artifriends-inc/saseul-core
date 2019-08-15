<?php

namespace Saseul\Core;

use Saseul\Constant\Structure;
use Saseul\Util\TypeChecker;

class Env
{
    public static $memcached = [
        'host' => 'memcached',
        'port' => 11211,
        'prefix' => '',
    ];

    public static $mongoDb = [
        'host' => 'mongo',
        'port' => 27017
    ];

    public static $nodeInfo = [
        'host' => '',
        'address' => '',
        'public_key' => '',
        'private_key' => ''
    ];

    public static $genesis = [
        'host' => '54.180.9.16',
        'address' => '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85',
        'coin_amount' => '1000000000000000',
        'deposit_amount' => '200000000000000',
        'key' => []
    ];

    public static function loadGenesisKey(string $path): void
    {
        $get_key = file_get_contents($path);
        $genesis_key = json_decode($get_key, true);

        self::$genesis['key'] = $genesis_key;
    }

    public static function load(): void
    {
        // TODO: env 파일 찾는 로직 필요.
        if (!is_file(SASEUL_DIR . '/env.default')) {
            return;
        }

        $env = file_get_contents(SASEUL_DIR . '/env.default');
        $env = json_decode($env, true);

        if (TypeChecker::StructureCheck(Structure::ENV, $env)) {
            self::$memcached = $env['memcached'];
            self::$mongoDb = $env['mongo_db'];
            self::$nodeInfo = $env['node_info'];
            self::$genesis = $env['genesis'];
        }

        self::loadGenesisKey(SASEUL_DIR . '/data/genesis_key.json');
    }
}
