<?php

namespace Saseul\Daemon;

use Saseul\Constant\Event;
use Saseul\Constant\Rule;
use Saseul\Core\Block;
use Saseul\Core\IMLog;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
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

    function round()
    {
        /** 원래 validator한테만 sync 받았었음. $lastBlock */

        # start;
        $this->stime = DateTime::Microtime();
        IMLog::add('[Log] round start : 0 s');

        $result = Event::NOTHING;
        Property::excludedHost($this->excludedHosts);

        # check round;
        $lastBlock = Block::GetLastBlock();
        $nodes = Tracker::getAccessibleNodes();
        $nodes = $this->mergedNode($nodes);

        IMLog::add('[CPU] check block & tracker : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');

        $myRound = $this->round_manager->myRound($lastBlock);
        $registOption = false;

        if ($this->netLastRoundNumber - $lastBlock['block_number'] < Rule::BUNCH) {
            $registOption = true;
        }
        $netRound = $this->round_manager->netRound($nodes, $registOption);
        $this->heartbeat = ((DateTime::Microtime() - $this->stime) / 1000000);
        IMLog::add('[Net] check round : ' . $this->heartbeat . ' s');

        $aliveNodes = $this->aliveNodes($nodes, array_keys($netRound));
        $this->tracker_manager->register($nodes, array_keys($netRound));
        $this->tracker_manager->collect($aliveNodes, array_keys($netRound));

        IMLog::add('[Net] check tracker : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');

        Property::aliveNode($aliveNodes);

        $fullValidators = Tracker::getAccessibleValidators();
        $aliveValidators = $this->aliveNodes($fullValidators, array_keys($netRound));

        $roundInfo = $this->round_manager->roundInfo($myRound, $netRound);
        $myRoundNumber = $roundInfo['my_round_number'];
        $netRoundNumber = $roundInfo['net_round_number'];
        $this->netLastRoundNumber = $netRoundNumber;

        IMLog::add('[CPU] ready consensus : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');

        if ($myRoundNumber === $netRoundNumber) {
            $result = $this->consensus($aliveValidators, $lastBlock, $myRound, $roundInfo);
            $completeTime = ((DateTime::Microtime() - $this->stime) / 1000000);
            $this->setLength($completeTime);
            IMLog::add('[All] consensus : ' . $completeTime . ' s');
            IMLog::add('[All] length : ' . $this->length);
            IMLog::add('#############################################');
        } else if ($myRoundNumber < $netRoundNumber) {
            $result = $this->sync($aliveNodes, $lastBlock, $myRoundNumber, $netRoundNumber);
            IMLog::add('[All] sync : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');
            IMLog::add('#############################################');
        }

        $this->finishingWork($result);
    }

    function consensus($aliveValidators, $lastBlock, $myRound, $roundInfo): string # Event
    {
        $lastStandardTimestamp = $lastBlock['s_timestamp'];
        $expectStandardTimestamp = $roundInfo['net_s_timestamp'];
        $lastBlockHash = $lastBlock['blockhash'];
        $roundLeader = $roundInfo['net_round_leader'];

        if ($expectStandardTimestamp === 0) {
            return Event::NOTHING;
        }

        # collect chunks;
        $this->commit_manager->init();

        $chunks = $this->commit_manager->collectApiChunk($aliveValidators, ['keys' => [], 'txs' => []], $lastStandardTimestamp, $expectStandardTimestamp);
        IMLog::add('[Log] transaction count : ' . count($chunks['txs']));
        IMLog::add('[Net] collectApiChunk : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');

        $chunks = $this->commit_manager->collectBroadcastChunk($aliveValidators, $chunks, $lastStandardTimestamp, $expectStandardTimestamp);
        IMLog::add('[Log] transaction count : ' . count($chunks['txs']));
        IMLog::add('[Net] collectBroadcastChunk : ' . ((DateTime::Microtime() - $this->stime) / 1000000) . ' s');

        $keys = $chunks['keys'];
        $transactions = $chunks['txs'];

        if (count($transactions) === 0) {
            Property::subjectNode($this->subjectNodeByAddress($aliveValidators, $roundLeader));
            return Event::WAIT;
        }

        # commit-manager init;
        # merge & sort;
        array_multisort($keys, $transactions);
        $completedTransactions = $this->commit_manager->completeTransactions($transactions);

        # make expect block info;
        $txCount = count($completedTransactions);
        $myBlockhash = Merkle::MakeBlockHash($lastBlockHash, Merkle::MakeMerkleHash($completedTransactions), $expectStandardTimestamp);
        $expectBlock = $this->commit_manager->nextBlock($lastBlock, $myBlockhash, $txCount, $expectStandardTimestamp);

        ## Consensus; ######################
        # check net hash;
        $roundKey = $myRound['decision']['round_key'];
        $myHashInfo = $this->hash_manager->myHashInfo($myRound, $expectBlock);
        $netHashInfo = $this->hash_manager->netHashInfo($roundKey, $aliveValidators);

        # find best;
        $bestHashInfo = $this->hash_manager->bestHashInfo($myHashInfo, $netHashInfo);
        $expectBlockhash = $bestHashInfo['blockhash'];

        # TODO: best hash 아니면 내 hash를 포기하자구.

        ## Consensus; ######################

        if ($expectBlockhash === $myBlockhash) {
            $this->commit_manager->commit($completedTransactions, $lastBlock, $expectBlock);
            $this->commit_manager->makeTransactionChunk($expectBlock, $transactions);

            IMLog::add(json_encode($expectBlock));

            /** tracker **/
            $this->tracker_manager->GenerateTracker();
            return Event::SUCCESS;
        }

        # banish;
        return Event::DIFFERENT;
    }
}