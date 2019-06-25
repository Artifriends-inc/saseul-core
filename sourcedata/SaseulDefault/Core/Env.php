<?php

namespace Saseul\Core;

use Saseul\Constant\Structure;
use Saseul\Util\TypeChecker;

class Env
{
    public static function load(): void
    {
        # TODO: env 파일 찾는 로직 필요.
        if (!is_file(SASEUL_DIR . '/env.default')) {
            return;
        }

        $env = file_get_contents(SASEUL_DIR. '/env.default');
        $env = json_decode($env, true);

        if (TypeChecker::StructureCheck(Structure::ENV, $env)) {
            self::$memcached = $env['memcached'];
            self::$mongoDb = $env['mongo_db'];
            self::$nodeInfo = $env['node_info'];
            self::$genesis = $env['genesis'];
        }
    }

    public static $memcached = [
        'host' => 'localhost',
        'port' => 11211,
        'prefix' => '',
    ];

    public static $mongoDb = [
        'host' => 'localhost',
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
        'key' => [
            'genesis_message' => 'Imagine Beyond and Invent Whatever, Wherever - Published by ArtiFriends. '
                . 'Thank you for help - YJ.Lee, JW.Lee, SH.Shin, YS.Han, WJ.Choi, DH.Kang, HG.Lee, KH.Kim, '
                . 'HK.Lee, JS.Han, SM.Park, SJ.Chae, YJ.Jeon, KM.Lee, JH.Kim, '
                . 'mika, ashal, datalater, namedboy, masterguru9, ujuc, johngrib, kimpi, greenmon, '
                . 'HS.Lee, TW.Nam, EH.Park, MJ.Mok',
            'special_thanks' => 'Michelle, Francis, JS.Han, Pang, Jeremy, JG, TY.Lee, SH.Ji, HK.Lim, IS.Choi, '
                . 'CH.Park, SJ.Park, DH.Shin and CK.Park',
            'etc_messages' => [
                [
                    'writer' => 'Michelle.Kim',
                    'message' => 'I love jjal. ',
                ],
                [
                    'writer' => 'Francis.W.Han',
                    'message' => 'khan@artifriends.com, I\'m here with JG and SK. ',
                ],
                [
                    'writer' => 'JG.Lee',
                    'message' => 'In the beginning God created the blocks and the chains. '
                        . 'God said, \'Let there be SASEUL\' and saw that it was very good. ',
                ],
                [
                    'writer' => 'namedboy',
                    'message' => 'This is \'SASEUL\', Welcome to new world.',
                ],
                [
                    'writer' => 'ujuc',
                    'message' => 'Hello Saseul! :)',
                ]
            ]
        ]
    ];
}