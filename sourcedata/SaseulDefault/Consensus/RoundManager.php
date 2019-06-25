<?php

namespace Saseul\Consensus;

use Saseul\Constant\Structure;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class RoundManager
{
    protected static $instance = null;
    protected $rest;

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

    public function myRound(array $lastBlock, int $length = 5): array
    {
        $myPrivateKey = NodeInfo::getPrivateKey();
        $myPublicKey = NodeInfo::getPublicKey();
        $myAddress = NodeInfo::getAddress();

        $round_number = $lastBlock['block_number'] + 1;
        $last_blockhash = $lastBlock['blockhash'];
        $last_s_timestamp = $lastBlock['s_timestamp'];
        $timestamp = DateTime::Microtime();
        $round_key = $this->roundKey($last_blockhash, $round_number);
        $expect_s_timestamp = Chunk::GetExpectStandardTimestamp($last_s_timestamp, $length);

        $decision = [
            'round_number' => $round_number,
            'last_blockhash' => $last_blockhash,
            'last_s_timestamp' => $last_s_timestamp,
            'timestamp' => $timestamp,
            'round_key' => $round_key,
            'expect_s_timestamp' => $expect_s_timestamp,
        ];

        $public_key = $myPublicKey;
        $hash = hash('sha256', json_encode($decision));
        $signature = Key::makeSignature($hash, $myPrivateKey, $myPublicKey);

        $round = [
            'decision' => $decision,
            'public_key' => $public_key,
            'hash' => $hash,
            'signature' => $signature,
        ];

        # save
        Property::round([$myAddress => $round]);

        return $round;
    }

    public function netRound(array $nodes, $opt = false) {
        $rounds = [];
        $hosts = [];

        foreach ($nodes as $node) {
            $hosts[] = $node['host'];
        }

        if ($opt === true) {
            $timestamp = DateTime::Microtime();

            $hostInfo = [
                'host' => NodeInfo::getHost(),
                'timestamp' => $timestamp,
                'signature' => Key::makeSignature(NodeInfo::getHost() . $timestamp, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey()),
                'public_key' => NodeInfo::getPublicKey(),
            ];

            $results = $this->rest->MultiPOST($hosts, 'round', ['host_info' => json_encode($hostInfo)]);
        } else {
            $results = $this->rest->MultiPOST($hosts, 'round');
        }

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            # check result;
            if (!isset($r['data']) || !is_array($r['data'])) {
                continue;
            }

            foreach ($r['data'] as $address => $round) {
                # check exists;
                if (isset($rounds[$address])) {
                    continue;
                }

                # check structure;
                if (TypeChecker::StructureCheck(Structure::ROUND, $round) === false) {
                    continue;
                }

                # check request is valid;
                if ($this->checkRequest($address, $round) === false) {
                    continue;
                }

                # add
                $rounds[$address] = $round;
            }
        }

        return $rounds;
    }

    public function roundInfo(array $myRound, array $netRound): array
    {
        $validatorAddress = Tracker::GetValidatorAddress();
        $myRoundNumber = $myRound['decision']['round_number'];
        $netRoundNumber = $myRoundNumber;
        $netStandardTimestamp = 0;
        $netRoundLeader = NodeInfo::getAddress();

        $lastStandardTimestamp = $myRound['decision']['last_s_timestamp'];

        foreach ($netRound as $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];

            if ($round_number > $netRoundNumber) {
                $netRoundNumber = $round_number;
            }
        }

        foreach ($netRound as $address => $round) {
            $decision = $round['decision'];
            $round_number = $decision['round_number'];
            $expect_s_timestamp = $decision['expect_s_timestamp'];

            if ($round_number === $netRoundNumber && in_array($address, $validatorAddress)) {
                if ($netStandardTimestamp === 0 || ($netStandardTimestamp > $expect_s_timestamp && $expect_s_timestamp > $lastStandardTimestamp)) {
                    $netStandardTimestamp = $expect_s_timestamp;
                    $netRoundLeader = $address;
                }
            }
        }

        $roundInfo = [
            'my_round_number' => $myRoundNumber,
            'net_round_number' => $netRoundNumber,
            'net_s_timestamp' => $netStandardTimestamp,
            'net_round_leader' => $netRoundLeader,
        ];

        # save
        Property::roundInfo($roundInfo);

        return $roundInfo;
    }

    public function roundKey($block, $round_number)
    {
        return hash('ripemd160', $block) . $round_number;
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
}
