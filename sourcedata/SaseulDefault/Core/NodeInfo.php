<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\System\IPChecker;
use Saseul\System\Key;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class NodeInfo
{
    protected static $nodeInfo = [];

    public static function isExist()
    {
        if (Env::$nodeInfo['host'] !== '' &&
            Env::$nodeInfo['address'] !== '' &&
            Env::$nodeInfo['public_key'] !== '' &&
            Env::$nodeInfo['private_key'] !== '') {
            return true;
        }

        return self::readNodeInfo();
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

    public static function getHost()
    {
        if (Env::$nodeInfo['host'] !== '') {
            return Env::$nodeInfo['host'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['host'];
    }

    public static function getAddress()
    {
        if (Env::$nodeInfo['address'] !== '') {
            return Env::$nodeInfo['address'];
        }

        self::readNodeInfo();

        return self::$nodeInfo['address'];
    }

    public static function resetNodeInfo()
    {
        if (is_file(Directory::NODE_INFO)) {
            unlink(Directory::NODE_INFO);
        }

        self::makeNodeInfoBase(Directory::NODE_INFO);
        $host = self::myIp();

        if ($host === '') {
            unlink(Directory::NODE_INFO);
            Logger::Error('sign error; ');

            return;
        }

        self::addHostInfo(Directory::NODE_INFO, $host);
    }

    protected static function readNodeInfo()
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

    protected static function makeNodeInfoBase($fileName)
    {
        $nodeHost = '';
        $nodePrivateKey = Key::makePrivateKey();
        $nodePublicKey = Key::makePublicKey($nodePrivateKey);
        $nodeAddress = Key::makeAddress($nodePublicKey);

        $nodeInfo = [
            'host' => $nodeHost,
            'private_key' => $nodePrivateKey,
            'public_key' => $nodePublicKey,
            'address' => $nodeAddress,
        ];

        file_put_contents($fileName, json_encode($nodeInfo));
    }

    private static function addHostInfo($fileName, $host): void
    {
        $nodeInfo = [
            'host' => $host,
            'private_key' => self::getPrivateKey(),
            'public_key' => self::getPublicKey(),
            'address' => self::getAddress(),
        ];

        file_put_contents($fileName, json_encode($nodeInfo));
    }

    private static function myIp(): string
    {
        $ip = IPChecker::getPublicIP();
        $string = bin2hex(random_bytes(16));

        $url = "http://{$ip}/sign?string={$string}";

        $rest = RestCall::GetInstance();
        $rs = $rest->GET($url);
        $rs = json_decode($rs, true);

        if (!isset($rs['data']['public_key']) || !isset($rs['data']['address']) || !isset($rs['data']['signature'])) {
            return '';
        }

        $publicKey = $rs['data']['public_key'];
        $address = $rs['data']['address'];
        $signature = $rs['data']['signature'];

        if (!Key::isValidAddress($address, $publicKey) || !Key::isValidSignature($string, $publicKey, $signature)) {
            return '';
        }

        return $ip;
    }
}
