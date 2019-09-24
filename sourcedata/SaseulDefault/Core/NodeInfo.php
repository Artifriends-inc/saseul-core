<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Util\TypeChecker;

class NodeInfo
{
    protected static $nodeInfo = [];

    /**
     * Node 정보가 있는지 파악한다.
     *
     * @return bool
     */
    public static function isExist(): bool
    {
        return !empty(Env::$nodeInfo['host'])
            && !empty(Env::$nodeInfo['address'])
            && !empty(Env::$nodeInfo['public_key'])
            && !empty(Env::$nodeInfo['private_key']);
    }

    public static function getPrivateKey()
    {
        if (Env::$nodeInfo['private_key'] !== '') {
            return Env::$nodeInfo['private_key'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['private_key'];
    }

    public static function getPublicKey()
    {
        if (Env::$nodeInfo['public_key'] !== '') {
            return Env::$nodeInfo['public_key'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['public_key'];
    }

    /**
     * 노드의 IP 주소를 읽어온다.
     *
     * @return string
     */
    public static function getHost(): string
    {
        if (Env::$nodeInfo['host'] !== '') {
            return Env::$nodeInfo['host'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['host'];
    }

    /**
     * 노드 계정 주소를 가져온다.
     *
     * @return string
     */
    public static function getAddress(): string
    {
        if (Env::$nodeInfo['address'] !== '') {
            return Env::$nodeInfo['address'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['address'];
    }

    /**
     * node.info 파일에서 node 정보를 읽어온다.
     *
     * @return bool
     */
    protected static function readNodeInfo(): bool
    {
        if (self::$nodeInfo === []) {
            if (!is_file(Directory::NODE_INFO)) {
                return false;
            }

            $nodeInfo = file_get_contents(Directory::NODE_INFO);
            $nodeInfo = json_decode($nodeInfo, true);

            if (!TypeChecker::StructureCheck(Structure::NODE_INFO, $nodeInfo)) {
                return false;
            }

            self::$nodeInfo = $nodeInfo;
        }

        return true;
    }
}
