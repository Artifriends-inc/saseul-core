<?php

namespace Saseul\Daemon;

use Saseul\Constant\Event;
use Saseul\Constant\Rule;
use Saseul\Core\Block;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Merkle;

class Validator extends Node
{
    protected static $instance;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function round()
    {
        // 원래 validator한테만 sync 받았었음. $lastBlock

        // start
        $this->stime = DateTime::Microtime();
        $this->log->debug('Round start', ['type' => 'round', 'sec' => 0]);

        $result = Event::NOTHING;
        Property::excludedHost($this->excludedHosts);

        // check round
        $lastBlock = Block::GetLastBlock();
        $nodes = Tracker::getAccessibleNodes();
        $nodes = $this->mergedNode($nodes);
        $nodes = $this->validators($nodes);

        $this->log->debug('Check block & tracker', ['type' => 'cpu', 'sec' => $this->nowTime()]);

        $myRound = $this->round_manager->myRound($lastBlock);
        $registOption = false;

        if ($this->netLastRoundNumber - $lastBlock['block_number'] < Rule::BUNCH) {
            $registOption = true;
        }
        $netRound = $this->round_manager->netRound($nodes, $registOption);
        $this->heartbeat = $this->nowTime();

        $this->log->debug('Check round', ['type' => 'net', 'sec' => $this->heartbeat]);

        $this->tracker_manager->register($nodes, array_keys($netRound));
        $nodes = Tracker::getAccessibleNodes();
        $aliveNodes = $this->aliveNodes($nodes, array_keys($netRound));
        $this->tracker_manager->collect($aliveNodes, array_keys($netRound));

        $this->log->debug('Check tracker', ['type' => 'net', 'sec' => $this->nowTime()]);

        Property::aliveNode($aliveNodes);

        $fullValidators = Tracker::getAccessibleValidators();
        $aliveValidators = $this->aliveNodes($fullValidators, array_keys($netRound));

        $roundInfo = $this->round_manager->roundInfo($myRound, $netRound);
        $myRoundNumber = $roundInfo['my_round_number'];
        $netRoundNumber = $roundInfo['net_round_number'];
        $this->netLastRoundNumber = $netRoundNumber;

        $this->log->debug('Ready consensus', ['type' => 'cpu', 'sec' => $this->nowTime()]);

        if ($myRoundNumber === $netRoundNumber) {
            $result = $this->consensus($aliveValidators, $lastBlock, $myRound, $roundInfo);
            $completeTime = $this->nowTime();
            $this->setLength($completeTime);

            $this->log->debug('Consensus', ['type' => 'all', 'consensus complete time' => $completeTime, 'length' => $this->length]);
        } elseif ($myRoundNumber < $netRoundNumber) {
            $result = $this->sync($aliveNodes, $lastBlock, $myRoundNumber, $netRoundNumber);

            $this->log->debug('Sync', ['type' => 'all', 'sync time' => $this->nowTime()]);
        }

        $this->finishingWork($result);
    }

    public function consensus($aliveValidators, $lastBlock, $myRound, $roundInfo): string // Event
    {
        $lastStandardTimestamp = $lastBlock['s_timestamp'];
        $expectStandardTimestamp = $roundInfo['net_s_timestamp'];
        $lastBlockHash = $lastBlock['blockhash'];
        $roundLeader = $roundInfo['net_round_leader'];

        if ($expectStandardTimestamp === 0) {
            return Event::NOTHING;
        }

        // collect chunks
        $this->commit_manager->init();

        $chunks = $this->commit_manager->collectApiChunk($aliveValidators, ['keys' => [], 'txs' => []], $lastStandardTimestamp, $expectStandardTimestamp);
        $this->log->debug('Collect api chunk', ['type' => 'log', 'count' => count($chunks['txs']), 'sec' => $this->nowTime()]);

        $chunks = $this->commit_manager->collectBroadcastChunk($aliveValidators, $chunks, $lastStandardTimestamp, $expectStandardTimestamp);
        $this->log->debug('Collect broadcast chunk', ['type' => 'log', 'count' => count($chunks['txs']), 'sec' => $this->nowTime()]);

        $keys = $chunks['keys'];
        $transactions = $chunks['txs'];

        if (count($transactions) === 0) {
            Property::subjectNode($this->subjectNodeByAddress($aliveValidators, $roundLeader));

            return Event::WAIT;
        }

        // commit-manager init
        // merge & sort
        array_multisort($keys, $transactions);
        $completedTransactions = $this->commit_manager->completeTransactions($transactions);

        // make expect block info
        $txCount = count($completedTransactions);
        $myBlockhash = Merkle::MakeBlockHash($lastBlockHash, Merkle::MakeMerkleHash($completedTransactions), $expectStandardTimestamp);
        $expectBlock = $this->commit_manager->nextBlock($lastBlock, $myBlockhash, $txCount, $expectStandardTimestamp);

        // Consensus
        // check net hash
        $roundKey = $myRound['decision']['round_key'];
        $myHashInfo = $this->hash_manager->myHashInfo($myRound, $expectBlock);
        $netHashInfo = $this->hash_manager->netHashInfo($roundKey, $aliveValidators);

        // find best
        $bestHashInfo = $this->hash_manager->bestHashInfo($myHashInfo, $netHashInfo);
        $expectBlockhash = $bestHashInfo['blockhash'];

        // TODO: best hash 아니면 내 hash를 포기하자구.

        // Consensus

        if ($expectBlockhash === $myBlockhash) {
            $this->commit_manager->commit($completedTransactions, $lastBlock, $expectBlock);
            $this->commit_manager->makeTransactionChunk($expectBlock, $transactions);

            $this->log->debug('Block data', [json_encode($expectBlock, JSON_THROW_ON_ERROR, 512)]);

            // tracker
            $this->tracker_manager->GenerateTracker();

            return Event::SUCCESS;
        }

        // banish
        return Event::DIFFERENT;
    }

    private function nowTime(): float
    {
        return (float) (DateTime::Microtime() - $this->stime) / 1000000;
    }
}
