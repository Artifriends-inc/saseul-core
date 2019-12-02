<?php

namespace Saseul\Daemon;

use Saseul\Constant\Event;
use Saseul\Constant\Rule;
use Saseul\Core\Block;
use Saseul\Core\Generation;
use Saseul\Core\Property;
use Saseul\Core\Tracker;

class Arbiter extends Node
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
        // start;
        Property::excludedHost($this->excludedHosts);

        $generation = Generation::current();
        $nodes = Tracker::getAccessibleNodes();
        $nodes = $this->mergedNode($nodes);
        $lastBlock = Block::getLastBlock();

        $myRound = $this->round_manager->myRound($lastBlock);
        $registOption = false;

        if ($this->netLastRoundNumber - $lastBlock['block_number'] < Rule::BUNCH) {
            $registOption = true;
        }
        $netRound = $this->round_manager->netRound($nodes, $registOption);

        $this->tracker_manager->register($nodes, array_keys($netRound));
        $nodes = Tracker::getAccessibleNodes();
        $aliveNodes = $this->aliveNodes($nodes, array_keys($netRound));
        $this->tracker_manager->collect($aliveNodes, array_keys($netRound));

        Property::aliveNode($aliveNodes);

        $roundInfo = $this->round_manager->roundInfo($myRound, $netRound);
        $myRoundNumber = $roundInfo['my_round_number'];
        $netRoundNumber = $roundInfo['net_round_number'];
        $this->netLastRoundNumber = $netRoundNumber;

        if ($myRoundNumber >= $netRoundNumber) {
            return;
        }

        $result = $this->sync($aliveNodes, $lastBlock, $myRoundNumber, $netRoundNumber);

        if ($result === Event::SUCCESS) {
            $this->changeGeneration($generation);
        }

        $this->finishingWork($result);
    }

    public function changeGeneration($generation)
    {
        $lastBlock = Block::getLastBlock();

        if ($lastBlock['block_number'] >= $generation['final_block_number']) {
            $sourceHash = Property::sourceHash();
            $sourceVersion = Property::sourceVersion();

            $finalBlockNumber = $generation['final_block_number'];

            $finalBlock = Block::blockByNumber($finalBlockNumber);
            $finalBlockhash = $finalBlock['blockhash'];

            $originBlockhash = $generation['origin_blockhash'];

            $nextFinalBlockNumber = $finalBlockNumber + Rule::GENERATION;

            Generation::finalize($originBlockhash, $finalBlockhash, $sourceHash, $sourceVersion);
            Generation::add($finalBlockhash, $finalBlockNumber, $nextFinalBlockNumber, $sourceHash, $sourceVersion);
        }
    }
}
