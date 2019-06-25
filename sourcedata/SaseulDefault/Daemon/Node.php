<?php

namespace Saseul\Daemon;

use Saseul\Common\Daemon;
use Saseul\Consensus\CommitManager;
use Saseul\Consensus\HashManager;
use Saseul\Consensus\RoundManager;
use Saseul\Consensus\SourceManager;
use Saseul\Consensus\SyncManager;
use Saseul\Consensus\TrackerManager;
use Saseul\Constant\Event;
use Saseul\Constant\Rank;
use Saseul\Constant\Structure;
use Saseul\Core\Block;
use Saseul\Core\Chunk;
use Saseul\Core\IMLog;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\Util\Logger;
use Saseul\Util\Merkle;
use Saseul\Util\TypeChecker;

class Node
{
    protected static $instance;
    protected $fail_count = 0;

    protected $round_manager;
    protected $commit_manager;
    protected $hash_manager;
    protected $sync_manager;
    protected $source_manager;
    protected $tracker_manager;

    protected $excludedHosts = [];

    protected $stime = 0;
    protected $heartbeat = 0;
    protected $length = 5;
    protected $netLastRoundNumber = 0;

    public function __construct()
    {
        $this->round_manager = RoundManager::GetInstance();
        $this->commit_manager = CommitManager::GetInstance();
        $this->hash_manager = HashManager::GetInstance();
        $this->sync_manager = SyncManager::GetInstance();
        $this->source_manager = SourceManager::GetInstance();
        $this->tracker_manager = TrackerManager::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function round()
    {
        IMLog::add('[Log] Nothing; ');
        sleep(1);
    }

    public function mergedNode($nodes)
    {
        $mergedNode = $nodes;
        $hosts = [];

        foreach ($mergedNode as $node) {
            $hosts[] = $node['host'];
        }

        foreach (Property::registerRequest() as $key => $node) {
            if (!TypeChecker::StructureCheck(Structure::TRACKER, $node)) {
                continue;
            }

            if (!in_array($node['host'], $hosts)) {
                $mergedNode[] = $node;
            }
        }

        return $mergedNode;
    }

    public function finishingWork(string $result): void
    {
        # TODO: ban이 리스크를 가짐.
        switch ($result)
        {
            case Event::DIFFERENT:
                $this->ban();
                break;
            case Event::NO_RESULT:
            case Event::WAIT:
                $this->resetFailCount();
                $this->exclude();
                break;
            case Event::NOTHING:
            case Event::SUCCESS:
            default:
                $this->resetFailCount();
                $this->resetexcludedHosts();
                break;
        }
    }

    function exclude(): void
    {
        $subjectNode = Property::subjectNode();

        if ($subjectNode['host'] && $subjectNode['host'] !== '') {
            $this->excludedHosts[$subjectNode['host']];
        }
    }

    function resetexcludedHosts()
    {
        # TODO: 바로 리셋해도 별 문제 없을까?
        $this->excludedHosts = [];
    }

    function setLength($completeTime = 0)
    {
        $length = (int)($completeTime + $this->heartbeat * 5) + 1;

        if ($length < 1) {
            $length = 1;
        }

        if ($length > 10) {
            $length = 10;
        }

        $this->length = $length;
    }

    public function resetFailCount()
    {
        $this->fail_count = 0;
    }

    public function increaseFailCount()
    {
        $this->fail_count++;
    }

    public function isTimeToSeparation()
    {
        return $this->fail_count >= 5;
    }

    public function getFailCount()
    {
        return $this->fail_count;
    }

    public function aliveNodes(array $nodes, array $alives)
    {
        $aliveNodes = [];
        $excludedHosts = $this->excludedHosts;

        foreach ($nodes as $node) {
            if (in_array($node['address'], $alives) && !in_array($node['host'], $excludedHosts)) {
                $aliveNodes[] = $node;
            }
        }

        return $aliveNodes;
    }

    public function subjectNodeByAddress(array $nodes, string $address)
    {
        $subjectNode = [];

        foreach ($nodes as $node) {
            if ($node['address'] === $address) {
                $subjectNode = $node;
                break;
            }
        }

        return $subjectNode;
    }

    public function subjectNodeByHost(array $nodes, string $host)
    {
        $subjectNode = [];

        foreach ($nodes as $node) {
            if ($node['host'] === $host) {
                $subjectNode = $node;
                break;
            }
        }

        return $subjectNode;
    }

    public function aliveArbiters(array $nodes) {
        $aliveArbiters = [];

        foreach ($nodes as $node) {
            if ($node['rank'] === Rank::ARBITER) {
                $aliveArbiters[] = $node;
            }
        }

        return $aliveArbiters;
    }

    public function update($aliveArbiters, $generation, $myRoundNumber): void
    {
        $originBlockhash = $generation['origin_blockhash'];
        $nextBlockNumber = $myRoundNumber;

        $netGenerationInfo = $this->source_manager->netGenerationInfo($aliveArbiters, $nextBlockNumber, $originBlockhash);

        if (count($netGenerationInfo) === 0) {
            return;
        }

        $target = $this->source_manager->selectGenerationInfo($netGenerationInfo);
        $targetInfo = $target['item'];
//        $unique = $target['unique'];
//
//        if ($unique === false) {
//            # fork 인지 ;; 정보 기록 필요함.
//            # 필요에 따라서 네트워크 ban;
//        }

        $host = $targetInfo['host'];
        $targetSourceHash = $targetInfo['source_hash'];
        $targetSourceVersion = $targetInfo['source_version'];

        $mySourceHash = Property::sourceHash();
        $mySourceVersion = Property::sourceVersion();

        # collect source hashs;
        $sourcehashs = $this->source_manager->collectSourcehashs($netGenerationInfo, $targetInfo);

        if (in_array($mySourceHash, $sourcehashs) || $mySourceVersion === $targetSourceVersion) {
            return;
        }

        Property::subjectNode($this->subjectNodeByHost($aliveArbiters, $host));

        # source change;
        $sourceArchive = $this->source_manager->getSource($host, $myRoundNumber);

        if ($sourceArchive === '') {
            # TODO: source 다른 놈한테 받아야 함.
            return;
        }

        $sourceFolder = $this->source_manager->makeSourceArchive($sourceArchive, $targetSourceHash);
        $this->source_manager->changeSourceFolder($sourceFolder);

        sleep(1);
        Daemon::restart();
    }

    public function sync($aliveNodes, $lastBlock, $myRoundNumber, $netRoundNumber): string
    {
        $netBunch = Block::bunchFinalNumber($netRoundNumber);
        $myBunch = Block::bunchFinalNumber($myRoundNumber);

        if ($netBunch !== $myBunch) {
            IMLog::add('[Sync] syncBunch');
            $syncResult = $this->syncBunch($aliveNodes, $myRoundNumber);
        } else {
            IMLog::add('[Sync] syncBlock');
            $syncResult = $this->syncBlock($aliveNodes, $lastBlock, $myRoundNumber);
        }

        return $syncResult;
    }

    public function syncBlock($aliveNodes, $lastBlock, $myRoundNumber): string
    {
        $netBlockInfo = $this->sync_manager->netBlockInfo($aliveNodes, $myRoundNumber);

        if (count($netBlockInfo) === 0) {
            IMLog::add('[Sync] netBlockInfo false ');
            return Event::NOTHING;
        }

        $target = $this->sync_manager->selectBlockInfo($netBlockInfo);
        $blockInfo = $target['item'];
//        $unique = $target['unique'];
//
//        if ($unique === false) {
//            # fork 인지 ;; 정보 기록 필요함.
//            # 필요에 따라서 네트워크 ban;
//        }

        $host = $blockInfo['host'];

        Property::subjectNode($this->subjectNodeByHost($aliveNodes, $host));

        $nextBlockhash = $blockInfo['blockhash'];
        $nextStandardTimestamp = $blockInfo['s_timestamp'];

        $transactions = $this->sync_manager->getBlockFile($host, $myRoundNumber);
        $syncResult = $this->syncCommit($transactions, $lastBlock, $nextBlockhash, $nextStandardTimestamp);

        return $syncResult;
    }

    public function syncBunch($aliveNodes, $myRoundNumber): string
    {
        $netBunchInfo = $this->sync_manager->netBunchInfo($aliveNodes, $myRoundNumber);

        if (count($netBunchInfo) === 0) {
            IMLog::add('[Sync] netBunchInfo false ');
            return Event::NOTHING;
        }

        $target = $this->sync_manager->selectBunchInfo($netBunchInfo);
        $bunchInfo = $target['item'];
//        $unique = $target['unique'];
//
//        if ($unique === false) {
//            # fork 인지 ;; 정보 기록 필요함.
//            # 필요에 따라서 네트워크 ban;
//        }

        $host = $bunchInfo['host'];
        Property::subjectNode($this->subjectNodeByHost($aliveNodes, $host));

        $nextBlockhash = $bunchInfo['blockhash'];
        $bunch = $this->sync_manager->getBunchFile($host, $myRoundNumber);

        if ($bunch === '') {
            IMLog::add('[Sync] getBunchFile false ');
            return Event::NO_RESULT;
        }

        $tempBunch = $this->sync_manager->makeTempBunch($bunch);
        $chunks = $this->sync_manager->bunchChunks($tempBunch);

        $first = true;

        foreach ($chunks as $chunk) {
            $lastBlock = Block::GetLastBlock();
            $transactions = Chunk::GetChunk("{$tempBunch}/{$chunk}");
            unlink("{$tempBunch}/{$chunk}");

            $fileBlockhash = mb_substr($chunk, 0, 64);
            $fileStandardTimestamp = mb_substr($chunk, 64, mb_strpos($chunk, '.') - 64);

            # find first;
            if ($first === true && $nextBlockhash !== $fileBlockhash) {
                continue;
            }

            $first = false;

            # commit-manager init;
            $syncResult = $this->syncCommit($transactions, $lastBlock, $fileBlockhash, $fileStandardTimestamp);

            if ($syncResult === Event::DIFFERENT) {
                return $syncResult;
            }
        }

        return Event::SUCCESS;
    }

    public function syncCommit(array $transactions, array $lastBlock, string $expectBlockhash, int $expectStandardTimestamp): string
    {
        $lastStandardTimestamp = $lastBlock['s_timestamp'];
        $lastBlockhash = $lastBlock['blockhash'];

        # commit-manager init;
        # merge & sort;
        $completedTransactions = $this->commit_manager->orderedTransactions($transactions, $lastStandardTimestamp, $expectStandardTimestamp);
        $completedTransactions = $this->commit_manager->completeTransactions($completedTransactions);

        # make expect block info;
        $txCount = count($completedTransactions);
        $myBlockhash = Merkle::MakeBlockHash($lastBlockhash, Merkle::MakeMerkleHash($completedTransactions), $expectStandardTimestamp);
        $expectBlock = $this->commit_manager->nextBlock($lastBlock, $expectBlockhash, $txCount, $expectStandardTimestamp);

        if ($expectBlockhash === $myBlockhash) {
            $this->commit_manager->commit($completedTransactions, $lastBlock, $expectBlock);
            $this->commit_manager->makeTransactionChunk($expectBlock, $transactions);

            /** tracker **/
            $this->tracker_manager->GenerateTracker();

            # ok;
            return Event::SUCCESS;
        }

        # banish;
        IMLog::add('[Sync] myBlockhash : ' . $myBlockhash);
        IMLog::add('[Sync] expectBlockhash : ' . $expectBlockhash);
        IMLog::add('[Sync] syncCommit false ');
        return Event::DIFFERENT;
    }

    public function ban(): void
    {
        if ($this->isTimeToSeparation())
        {
            # ban;
            $subjectNode = Property::subjectNode();
            Tracker::banHost($subjectNode['host']);
            $this->resetFailCount();
            return;
        }

        $this->increaseFailCount();
    }
}
