<?php

namespace Saseul\Daemon;

use Saseul\Constant\Event;
use Saseul\Constant\Rule;
use Saseul\Core\Block;
use Saseul\Core\Generation;
use Saseul\Core\Property;
use Saseul\Core\Tracker;

class Light extends Node
{
    protected static $instance;

    protected $updateCheck = true;

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function round()
    {
        // start
        Property::excludedHost($this->excludedHosts);

        $generation = Generation::current();
        $nodes = Tracker::getAccessibleNodeList();
        $nodes = $this->mergedNode($nodes);
        $lastBlock = Block::getLastBlock();

        $myRound = $this->round_manager->myRound($lastBlock);
        $this->log->debug('round data', ['my' => $myRound, 'this net' => $this->netLastRoundNumber]);

        $registOption = false;
        if ($this->netLastRoundNumber - $lastBlock['block_number'] < Rule::BUNCH) {
            $registOption = true;
        }

        $netRound = $this->round_manager->netRound($nodes, $registOption);
        $this->log->debug('round data', ['my' => $myRound, 'net' => $netRound]);

        $this->tracker_manager->register($nodes, array_keys($netRound));
        $nodes = Tracker::getAccessibleNodeList();
        $aliveNodes = $this->aliveNodes($nodes, array_keys($netRound));
        $aliveArbiter = $this->aliveArbiters($aliveNodes);
        $this->log->debug('node list', ['node' => $aliveNodes, 'arbiter' => $aliveArbiter]);
        $this->tracker_manager->collect($aliveNodes, array_keys($netRound));

        Property::aliveNode($aliveNodes);

        $roundInfo = $this->round_manager->roundInfo($myRound, $netRound);
        $this->log->debug('round info', ['round info' => $roundInfo]);

        $myRoundNumber = $roundInfo['my_round_number'];
        $netRoundNumber = $roundInfo['net_round_number'];
        $this->netLastRoundNumber = $netRoundNumber;

        $this->log->debug('update check', [$this->updateCheck]);
        if ($this->updateCheck === true) {
            $this->update($aliveArbiter, $generation, $myRoundNumber);
            $this->updateCheck = false;
        }

        if ($myRoundNumber >= $netRoundNumber) {
            return;
        }

        $result = $this->sync($aliveNodes, $lastBlock, $myRoundNumber, $netRoundNumber);

        if ($result === Event::SUCCESS) {
            $this->changeGeneration($aliveArbiter, $generation);
        }

        $this->finishingWork($result);
    }

    public function changeGeneration($aliveArbiter, $generation)
    {
        $lastBlock = Block::getLastBlock();

        if ($lastBlock['block_number'] >= $generation['final_block_number']) {
            $sourceHash = Property::sourceHash();
            $sourceVersion = Property::sourceVersion();

            $finalBlockNumber = $generation['final_block_number'];
            $finalBlock = Block::getBlockInfoByNumber($finalBlockNumber);
            $finalBlockhash = $finalBlock['blockhash'];

            $originBlockhash = $generation['origin_blockhash'];

            $nextFinalBlockNumber = $finalBlockNumber + Rule::GENERATION;

            Generation::finalize($originBlockhash, $finalBlockhash, $sourceHash, $sourceVersion);
            Generation::add($finalBlockhash, $finalBlockNumber, $nextFinalBlockNumber, '', '');

            $this->update($aliveArbiter, $generation, $finalBlockNumber);
        }
    }
}
