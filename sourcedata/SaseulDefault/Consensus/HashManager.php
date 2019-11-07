<?php

namespace Saseul\Consensus;

use Saseul\Constant\Structure;
use Saseul\Core\NodeInfo;
use Saseul\Core\Property;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class HashManager
{
    private static $instance = null;
    private $rest;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function myHashInfo($myRound, $expectBlock)
    {
        $myAddress = NodeInfo::getAddress();

        $my_decision = $myRound['decision'];
        $round_number = $my_decision['round_number'];
        $last_blockhash = $my_decision['last_blockhash'];
        $round_key = $my_decision['round_key'];

        $now = DateTime::Microtime();

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'blockhash' => $expectBlock['blockhash'],
            's_timestamp' => $expectBlock['s_timestamp'],
            'transaction_count' => $expectBlock['transaction_count'],
            'timestamp' => $now,
            'round_key' => $round_key,
        ];

        $public_key = NodeInfo::getPublicKey();
        $hash = hash('sha256', json_encode($decision));
        $signature = Key::makeSignature($hash, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());

        $myHashInfo = [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];

        // save
        Property::hashInfo($round_key, [$myAddress => $myHashInfo]);

        return $myHashInfo;
    }

    public function netHashInfo($roundKey, $aliveValidators)
    {
        $hashInfos = [];
        $hosts = [];

        foreach ($aliveValidators as $validator) {
            $hosts[] = $validator['host'];
        }

        // 3 times;
        for ($i = 0; $i < 3; $i++) {
            $now = DateTime::Microtime();

            $results = $this->rest->MultiPOST($hosts, 'hashinfo', ['round_key' => $roundKey]);

            foreach ($results as $item) {
                $r = json_decode($item['result'], true);

                if (!isset($r['data']) || !is_array($r['data'])) {
                    continue;
                }

                foreach ($r['data'] as $address => $blockhash) {
                    if (isset($hashInfos[$address])) {
                        continue;
                    }

                    if (TypeChecker::StructureCheck(Structure::HASH_INFO, $blockhash) === false) {
                        continue;
                    }

                    if ($this->checkRequest($address, $blockhash) === false) {
                        continue;
                    }

                    $hashInfos[$address] = $blockhash;
                }
            }

            if (count($hashInfos) === count($aliveValidators)) {
                return $hashInfos;
            }

            $wait = DateTime::Microtime() - $now;

            if ($wait < 200000) {
                usleep(200000 - $wait);
            }
        }

        return $hashInfos;
    }

    public function bestHashInfo($myHashInfo, $netHashInfo)
    {
        if (count($netHashInfo) === 0) {
            return [
                'address' => NodeInfo::getAddress(),
                'blockhash' => $myHashInfo['decision']['blockhash'],
            ];
        }

        $best = [];
        $sign = false;

        $bestAddress = '';
        $bestBlockhash = '';

        foreach ($netHashInfo as $address => $item) {
            if ($best === []) {
                $best = $item;
                $bestAddress = $address;
                $bestBlockhash = $best['decision']['blockhash'];

                continue;
            }

            $blockhash = $item['decision']['blockhash'];
            $s_timestamp = $item['decision']['s_timestamp'];

            $best_blockhash = $best['decision']['blockhash'];
            $best_s_timestamp = $best['decision']['s_timestamp'];

            if ($blockhash !== $best_blockhash) {
                if ($s_timestamp < $best_s_timestamp) {
                    $best = $item;
                    $bestAddress = $address;
                    $bestBlockhash = $best['decision']['blockhash'];

                    continue;
                }

                // different;
                $sign = true;
            }
        }

        if ($sign === true) {
            return [
                'address' => '',
                'blockhash' => '',
            ];
        }

        return [
            'address' => $bestAddress,
            'blockhash' => $bestBlockhash,
        ];
    }

    public function checkRequest($address, $value): bool
    {
        $round_number = $value['decision']['round_number'];
        $last_blockhash = $value['decision']['last_blockhash'];
        $round_key = $value['decision']['round_key'];
        $public_key = $value['public_key'];
        $signature = $value['signature'];
        $hash = hash('sha256', json_encode($value['decision']));

        return Key::isValidSignature($hash, $public_key, $signature)
            && (Key::makeAddress($public_key) === $address)
            && ($this->roundKey($last_blockhash, $round_number) === $round_key);
    }

    public function roundKey($block, $round_number)
    {
        return hash('ripemd160', $block) . $round_number;
    }
}
