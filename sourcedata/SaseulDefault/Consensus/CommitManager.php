<?php

namespace Saseul\Consensus;

use ReflectionClass;
use Saseul\Constant\Decision;
use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Status\Fee;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

// TODO: TEST 코드가 추가 되어야한다.
class CommitManager
{
    private static $instance = null;

    private $db;
    private $rest;
    private $status_manager;

    private $streamLog;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rest = RestCall::GetInstance();
        $this->status_manager = new StatusManager();

        $this->streamLog = Logger::getStreamLogger(Logger::DAEMON);
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        $this->status_manager->Reset();
    }

    public function nextBlock($lastBlock, $blockhash, int $txCount, $standardTimestamp)
    {
        return [
            'block_number' => ((int) $lastBlock['block_number'] + 1),
            'last_blockhash' => $lastBlock['blockhash'],
            'blockhash' => $blockhash,
            'transaction_count' => $txCount,
            's_timestamp' => $standardTimestamp,
            'timestamp' => DateTime::Microtime(),
        ];
    }

    public function commit($transactions, $lastBlock, $expectBlock)
    {
        if (count($transactions) === 0) {
            return;
        }

        $blockhash = $expectBlock['blockhash'];
        $s_timestamp = $expectBlock['s_timestamp'];

        Fee::SetBlockhash($blockhash);
        Fee::SetStandardTimestamp($s_timestamp);

        $this->status_manager->Preprocess();
        $this->status_manager->Save();
        $this->status_manager->Postprocess();

        $this->commitTransaction($transactions, $expectBlock);
        $this->commitBlock($expectBlock);

        Chunk::RemoveAPIChunk($lastBlock['s_timestamp']);
        Chunk::RemoveBroadcastChunk($lastBlock['s_timestamp']);
    }

    public function orderedTransactions($transactions, $minTimestamp, $maxTimestamp)
    {
        $orderKey = [];
        $txs = [];

        $this->status_manager->Reset();

        foreach ($transactions as $key => $item) {
            if (TypeChecker::StructureCheck(Structure::TX_ITEM, $item) === false) {
                continue;
            }

            $transaction = $item['transaction'];
            $thash = hash('sha256', json_encode($transaction));
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];
            $timestamp = $transaction['timestamp'];

            if ($timestamp > $maxTimestamp || $timestamp < $minTimestamp) {
                continue;
            }

            // TODO: 호출되는 Transaction의 type들이 최초 한 번 만 생성되어 계속 사용되는게 맞는지 계속 재생성인지 확인 필요
            $transactionType = $this->createTransactionTypeInstance($type);
            $transactionType->initialize(
                $transaction,
                $thash,
                $public_key,
                $signature
            );
            $validity = $transactionType->getValidity();
            $transactionType->loadStatus();

            if ($validity === false) {
                continue;
            }

            $txs[] = [
                'thash' => $thash,
                'timestamp' => $timestamp,
                'transaction' => $transaction,
                'public_key' => $public_key,
                'signature' => $signature,
                'result' => '',
            ];

            $orderKey[] = $timestamp . $thash;
        }

        array_multisort($orderKey, $txs);

        return $txs;
    }

    public function collectChunk(array $results, array $oldChunks, int $minTime, int $maxTime)
    {
        $t = [];
        $max = 0;
        $chunks = $oldChunks;

        foreach ($results as $rs) {
            $result = json_decode($rs['result'], true);

            if ($rs['exec_time'] > $max) {
                $max = $rs['exec_time'];
            }

//            $t[] = [
//                'host' => $rs['host'],
//                'exec_time' => $rs['exec_time'],
//                'cnt' => count($result['data']['items']),
//            ];

            // check structure;
            if (!isset($result['data']['items']) || !is_array($result['data']['items'])) {
                continue;
            }

            foreach ($result['data']['items'] as $item) {
                // check structure;
                if (TypeChecker::StructureCheck(Structure::BROADCAST_ITEM, $item) === false) {
                    continue;
                }

                $address = $item['address'];
                $file_name = $item['file_name'];
                $transactions = $item['transactions'];
                $public_key = $item['public_key'];
                $content_signature = $item['content_signature'];

                // check request is valid;
                if (!Key::isValidAddress($address, $public_key) ||
                    !Chunk::isValidContentSignaure($public_key, DateTime::toTime($maxTime), $content_signature, $transactions)) {
                    continue;
                }

                // add broadcast chunk;
                Chunk::makeBroadcastChunk($file_name, $public_key, $content_signature, $transactions);

                $chunks = $this->mergedChunks($chunks, $transactions, $minTime, $maxTime);
            }
        }

        $this->streamLog->debug('Commit collect chunk', ['maximum_exec_time' => $max]);

        return $chunks;
    }

    public function collectApiChunk(array $aliveValidators, array $oldChunks, int $minTime, int $maxTime)
    {
        $hosts = [];
        $chunks = $oldChunks;

        $reqTime = DateTime::Microtime();
        $data = [
            'min_time' => $minTime,
            'max_time' => $maxTime,
            'req_time' => $reqTime,
            'public_key' => NodeInfo::getPublicKey(),
            'signature' => Key::makeSignature($reqTime, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey()),
        ];

        foreach ($aliveValidators as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'broadcast2', $data, false, [], 3);

        return $this->collectChunk($results, $chunks, $minTime, $maxTime);
    }

    public function collectBroadcastChunk(array $aliveValidators, array $oldChunks, int $minTime, int $maxTime)
    {
        $reqTime = DateTime::Microtime();

        $chunks = $oldChunks;
        $hosts = [];

        foreach ($aliveValidators as $node) {
            $hosts[] = $node['host'];
        }

        // try;
        $broadcastCode = Chunk::broadcastCode(DateTime::toTime($maxTime));
        $data = [
            'broadcast_code' => $broadcastCode,
            's_timestamp' => $maxTime,
            'req_time' => $reqTime,
            'public_key' => NodeInfo::getPublicKey(),
            'signature' => Key::makeSignature($reqTime, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey()),
        ];

        $results = $this->rest->MultiPOST($hosts, 'broadcast3', $data, false, [], 3);
        $chunks = $this->collectChunk($results, $chunks, $minTime, $maxTime);
        $broadcastCodes = $this->collectBroadcastCode($results, []);
        $most = TypeChecker::findMostItem(array_values($broadcastCodes), 'broadcast_code');

        // 이거 살리면 확실하게 Stable;
//        if ($most['unique'] === true && $most['item']['broadcast_code'] === $broadcastCode) {
//            return $chunks;
//        }
        if ($most['item']['broadcast_code'] === $broadcastCode) {
            return $chunks;
        }

        // retry;
        $broadcastCode = Chunk::broadcastCode(DateTime::toTime($maxTime));
        $data = [
            'broadcast_code' => $broadcastCode,
            's_timestamp' => $maxTime,
            'req_time' => $reqTime,
            'public_key' => NodeInfo::getPublicKey(),
            'signature' => Key::makeSignature($reqTime, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey()),
        ];

        $results = $this->rest->MultiPOST($hosts, 'broadcast3', $data, false, [], 3);

        return $this->collectChunk($results, $chunks, $minTime, $maxTime);
        // TODO : 리스크 실험 해야함.
        //   이거 살리면 확실하게 Stable;
//        $broadcastCodes = $this->collectBroadcastCode($results, $broadcastCodes);
//        $most = TypeChecker::findMostItem(array_values($broadcastCodes), 'broadcast_code');
//
//        if ($most['item']['broadcast_code'] === $broadcastCode) {
//            return $chunks;
//        }
//
//        return ['keys' => [], 'txs' => []];
    }

    public function collectBroadcastCode($results, $oldCodes)
    {
        $broadcastCodes = $oldCodes;

        foreach ($results as $rs) {
            $result = json_decode($rs['result'], true);

            // check structure;
            if (!isset($result['data'])) {
                continue;
            }

            // check structure;
            if (TypeChecker::StructureCheck(Structure::BROADCAST_RESULT, $result['data']) === false) {
                continue;
            }

            $broadcastCodes[$result['data']['address']] = [
                'address' => $result['data']['address'],
                'broadcast_code' => $result['data']['broadcast_code'],
            ];
        }

        return $broadcastCodes;
    }

    public function mergedChunks(array $oldChunks, array $transactions, $minTimestamp, $maxTimestamp)
    {
        $keys = $oldChunks['keys'];
        $txs = $oldChunks['txs'];

        foreach ($transactions as $item) {
            if (TypeChecker::StructureCheck(Structure::TX_ITEM, $item) === false) {
                continue;
            }

            $transaction = $item['transaction'];
            $thash = hash('sha256', json_encode($transaction));
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];
            $timestamp = $transaction['timestamp'];
            $key = $timestamp . $thash;

            if ($timestamp > $maxTimestamp || $minTimestamp >= $timestamp || in_array($key, $keys)) {
                continue;
            }

            // TODO: 호출되는 Transaction의 type들이 최초 한 번 만 생성되어 계속 사용되는게 맞는지 계속 재생성인지 확인 필요
            $transactionType = $this->createTransactionTypeInstance($type);
            $transactionType->initialize(
                $transaction,
                $thash,
                $public_key,
                $signature
            );
            $validity = $transactionType->getValidity();

            if ($validity === false) {
                continue;
            }

            $transactionType->loadStatus();

            $keys[] = $key;

            $txs[] = [
                'thash' => $thash,
                'timestamp' => $timestamp,
                'transaction' => $transaction,
                'public_key' => $public_key,
                'signature' => $signature,
                'result' => '',
            ];
        }

        return [
            'keys' => $keys,
            'txs' => $txs,
        ];
    }

    public function completeTransactions($transactions)
    {
        // load status
        $this->status_manager->Load();

        foreach ($transactions as $key => $item) {
            $transaction = $item['transaction'];
            $thash = $item['thash'];
            $public_key = $item['public_key'];
            $signature = $item['signature'];

            $type = $transaction['type'];

            // TODO: 호출되는 Transaction의 type들이 최초 한 번 만 생성되어 계속 사용되는게 맞는지 계속 재생성인지 확인 필요
            $transactionType = $this->createTransactionTypeInstance($type);
            $transactionType->initialize(
                $transaction,
                $thash,
                $public_key,
                $signature
            );
            $transactionType->getStatus();
            $result = $transactionType->makeDecision();

            $transactions[$key]['result'] = $result;

            if ($result === Decision::REJECT) {
                continue;
            }

            $transactionType->setStatus();
        }

        return $transactions;
    }

    /**
     * 등록된 Transaction 에 Blockhash 값을 입력한다.
     *
     * @param array $transactions Transaction List
     * @param array $expectBlock  이번 commit 되는 블록 정보
     */
    public function commitTransaction(array $transactions, array $expectBlock): void
    {
        $blockhash = $expectBlock['blockhash'];

        $operationList = [];
        foreach ($transactions as $transaction) {
            $transaction['block'] = $blockhash;

            $operationList[] = [
                'updateOne' => [
                    [
                        'thash' => $transaction['thash'],
                        'timestamp' => $transaction['timestamp'],
                    ],
                    [
                        '$set' => $transaction,
                    ],
                    [
                        'upsert' => true,
                    ]
                ]
            ];
        }

        $this->db->getTransactionsCollection()->bulkWrite($operationList);
    }

    /**
     * 블록 정보를 받아 저장한다.
     *
     * @param array $expectBlock 이번 commit 되는 블록 정보
     */
    public function commitBlock(array $expectBlock): void
    {
        if (empty($expectBlock)) {
            return;
        }

        $this->db->getBlocksCollection()->insertOne($expectBlock);
    }

    public function makeTransactionChunk($expectBlock, $transactions)
    {
        $block_number = $expectBlock['block_number'];
        $chunkname = $expectBlock['blockhash'] . $expectBlock['s_timestamp'] . '.json';

        $transaction_dir = Chunk::txSubDir($block_number);
        $make = Chunk::makeTxSubDir($block_number);

        if ($make === true) {
            Chunk::makeTxArchive((int) $block_number - 1);
        }

        $transaction_chunk = Directory::TRANSACTIONS . '/' . $transaction_dir . '/' . $chunkname;

        if (file_exists($transaction_chunk)) {
            return;
        }

        $file = fopen($transaction_chunk, 'a');

        foreach ($transactions as $transaction) {
            fwrite($file, json_encode($transaction) . ",\n");
        }

        fclose($file);
    }

    private function createTransactionTypeInstance(string $type)
    {
        $classNameWithPath = "Saseul\\Custom\\Transaction\\{$type}";

        return (new ReflectionClass($classNameWithPath))->newInstance();
    }
}
