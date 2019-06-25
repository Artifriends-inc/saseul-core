<?php

namespace Saseul\System;

use Saseul\Constant\Account;
use Saseul\Util\DateTime;

class Key
{
    public static function makePrivateKey()
    {
        return bin2hex(random_bytes(24)) . str_pad(dechex(DateTime::Microtime()), 16, 0, 0);
    }

    public static function makePublicKey($privateKey)
    {
        return bin2hex(ed25519_publickey(hex2bin($privateKey)));
    }

    public static function makeAddress($publicKey)
    {
        $p0 = Account::ADDRESS_PREFIX[0];
        $p1 = Account::ADDRESS_PREFIX[1];
        $s1 = $p1 . hash('ripemd160', hash('sha256', $p0 . $publicKey));

        return $s1 . substr(hash('sha256', hash('sha256', $s1)), 0, 4);
    }

    public static function makeSignature($str, $privateKey, $publicKey)
    {
        return bin2hex(ed25519_sign($str, hex2bin($privateKey), hex2bin($publicKey)));
    }

    public static function isValidSignature($str, $publicKey, $signature)
    {
        return ed25519_sign_open($str, hex2bin($publicKey), hex2bin($signature));
    }

    public static function isValidAddress($address, $publicKey): bool
    {
        return !empty($address)
            && (mb_strlen($address) === Account::ADDRESS_SIZE)
            && (self::makeAddress($publicKey) === $address);
    }
}
