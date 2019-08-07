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
        // start;
        Property::excludedHost($this->excludedHosts);

        $generation = Generation::current();
        $nodes = Tracker::getAccessibleNodes();
        $nodes = $this->mergedNode($nodes);
        $lastBlock = Block::GetLastBlock();

        $myRound = $this->round_manager->myRound($lastBlock);
        $registOption = false;

        if ($this->netLastRoundNumber - $lastBlock['block_number'] < Rule::BUNCH) {
            $registOption = true;
        }
        $netRound = $this->round_manager->netRound($nodes, $registOption);

        $this->tracker_manager->register($nodes, array_keys($netRound));
        $nodes = Tracker::getAccessibleNodes();
        $aliveNodes = $this->aliveNodes($nodes, array_keys($netRound));
        $aliveArbiter = $this->aliveArbiters($aliveNodes);
        $this->tracker_manager->collect($aliveNodes, array_keys($netRound));

        Property::aliveNode($aliveNodes);

        $roundInfo = $this->round_manager->roundInfo($myRound, $netRound);

        $myRoundNumber = $roundInfo['my_round_number'];
        $netRoundNumber = $roundInfo['net_round_number'];
        $this->netLastRoundNumber = $netRoundNumber;

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
        $lastBlock = Block::GetLastBlock();

        if ($lastBlock['block_number'] >= $generation['final_block_number']) {
            $sourceHash = Property::sourceHash();
            $sourceVersion = Property::sourceVersion();

            $finalBlockNumber = $generation['final_block_number'];
            $finalBlock = Block::blockByNumber($finalBlockNumber);
            $finalBlockhash = $finalBlock['blockhash'];

            $originBlockhash = $generation['origin_blockhash'];

            $nextFinalBlockNumber = $finalBlockNumber + Rule::GENERATION;

            Generation::finalize($originBlockhash, $finalBlockhash, $sourceHash, $sourceVersion);
            Generation::add($finalBlockhash, $finalBlockNumber, $nextFinalBlockNumber, '', '');

            $this->update($aliveArbiter, $generation, $finalBlockNumber);
        }
    }
}
