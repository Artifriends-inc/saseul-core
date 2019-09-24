<?php

namespace Saseul\Core;

class NodeInfo
{
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

    /**
     * 노드 Private key 값을 읽어온다.
     *
     * @return string
     */
    public static function getPrivateKey(): string
    {
        return Env::$nodeInfo['private_key'];
    }

    /**
     * 노드 public key 값을 읽어온다.
     *
     * @return string
     */
    public static function getPublicKey(): string
    {
        return Env::$nodeInfo['public_key'];
    }

    /**
     * 노드의 IP 주소를 읽어온다.
     *
     * @return string
     */
    public static function getHost(): string
    {
        return Env::$nodeInfo['host'];
    }

    /**
     * 노드 계정 주소를 가져온다.
     *
     * @return string
     */
    public static function getAddress(): string
    {
        return Env::$nodeInfo['address'];
    }
}
