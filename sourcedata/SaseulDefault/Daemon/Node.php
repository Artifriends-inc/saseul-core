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
    protected $logger;
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

    // TODO: Static 에 대해서 고민 후 설계 변경
    public function __construct()
    {
        $this->round_manager = RoundManager::GetInstance();
        $this->commit_manager = CommitManager::GetInstance();
        $this->hash_manager = HashManager::GetInstance();
        $this->sync_manager = SyncManager::GetInstance();
        $this->source_manager = SourceManager::GetInstance();
        $this->tracker_manager = TrackerManager::GetInstance();

        $this->tracker_manager->GenerateTracker();
        $this->logger = Logger::getLogger('Daemon');
        Tracker::setMyHost();
    }

    // TODO: Node 를 상속 받는 클래스들이 메서드를 재 정의하고 있음 이유 확인
    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function round()
    {
        IMLog::add('[Log] Nothing; ');
        $this->logger->debug('Nothiong');
        sleep(1);
    }

    // TODO: 접근 지정자를 public에서 protected로 변경
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

    // TODO: 접근 지정자를 public에서 protected로 변경
    public function finishingWork(string $result): void
    {
        // TODO: ban이 리스크를 가짐.
        switch ($result) {
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

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 메서드 이름 명확하게 변경
    public function exclude(): void
    {
        $subjectNode = Property::subjectNode();

        if ($subjectNode['host'] && $subjectNode['host'] !== '') {
            $this->excludedHosts[$subjectNode['host']];
        }
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 메서드 이름 명확하게 변경
    public function resetexcludedHosts()
    {
        // TODO: 바로 리셋해도 별 문제 없을까?
        $this->excludedHosts = [];
    }

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
    public function setLength($completeTime = 0)
    {
        $length = (int) ($completeTime + $this->heartbeat * 5) + 1;

        if ($length < 1) {
            $length = 1;
        }

        if ($length > 10) {
            $length = 10;
        }

        $this->length = $length;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경
    public function resetFailCount()
    {
        $this->fail_count = 0;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경
    public function increaseFailCount()
    {
        $this->fail_count++;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경
    public function isTimeToSeparation()
    {
        return $this->fail_count >= 5;
    }

    // TODO: 사용되고 있지 않으므로 변경
    public function getFailCount()
    {
        return $this->fail_count;
    }

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
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

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
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

    // TODO: 접근 지정자를 public 에서 private 으로 변경
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

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
    public function aliveArbiters(array $nodes)
    {
        $aliveArbiters = [];

        foreach ($nodes as $node) {
            if ($node['rank'] === Rank::ARBITER) {
                $aliveArbiters[] = $node;
            }
        }

        return $aliveArbiters;
    }

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
    public function validators(array $nodes)
    {
        $allValidators = Tracker::GetValidatorAddress();
        $validators = [];

        foreach ($nodes as $node) {
            if (in_array($node['address'], $allValidators)) {
                $validators[] = $node;
            }
        }

        return $validators;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 메서드 이름 명확하게 변경
    public function update($aliveArbiters, $generation, $myRoundNumber): void
    {
        $originBlockhash = $generation['origin_blockhash'];
        $nextBlockNumber = $myRoundNumber;

        $netGenerationInfo = $this->source_manager->netGenerationInfo($aliveArbiters, $nextBlockNumber, $originBlockhash);
        $this->logger->debug('network generation info', [$netGenerationInfo]);

        if (count($netGenerationInfo) === 0) {
            return;
        }

        $target = $this->source_manager->selectGenerationInfo($netGenerationInfo);
        $targetInfo = $target['item'];
        // Todo: 해당 주석부분을 확인해서 변경해야한다.
        // $unique = $target['unique'];
        //
        // if ($unique === false) {
        //    # fork 인지 ;; 정보 기록 필요함.
        //    # 필요에 따라서 네트워크 ban;
        // }

        $host = $targetInfo['host'];
        $targetSourceHash = $targetInfo['source_hash'];
        $targetSourceVersion = $targetInfo['source_version'];

        $mySourceHash = Property::sourceHash();
        $mySourceVersion = Property::sourceVersion();

        // collect source hashs
        $sourcehashs = $this->source_manager->collectSourcehashs($netGenerationInfo, $targetInfo);

        $this->logger->debug(
            'source hash',
            [
                'target source hash' => ['target info' => $target, 'source hash' => $sourcehashs],
                'my source hash' => ['source hash' => $mySourceHash, 'source version' => $mySourceVersion]
            ]
        );

        if (in_array($mySourceHash, $sourcehashs) || $mySourceVersion === $targetSourceVersion) {
            return;
        }

        Property::subjectNode($this->subjectNodeByHost($aliveArbiters, $host));

        // source change
        $sourceArchive = $this->source_manager->getSource($host, $myRoundNumber);
        $this->logger->debug('Source archive', ['host' => $host, 'my round number' => $myRoundNumber, 'archive' => $sourceArchive]);

        if ($sourceArchive === '') {
            // TODO: source 다른 놈한테 받아야 함.
            return;
        }

        $sourceFolder = $this->source_manager->restoreSource($sourceArchive, $targetSourceHash);
        $this->source_manager->changeSourceFolder($sourceFolder);

        sleep(1);
        Daemon::restart();
    }

    // TODO: 접근 지정자를 public 에서 protected 으로 변경
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
        $this->logger->debug('sync', ['result' => $syncResult]);

        return $syncResult;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 주석 부분 당장 필요 없으면 제거
    public function syncBlock($aliveNodes, $lastBlock, $myRoundNumber): string
    {
        $netBlockInfo = $this->sync_manager->netBlockInfo($aliveNodes, $myRoundNumber);

        if (count($netBlockInfo) === 0) {
            IMLog::add('[Sync] netBlockInfo false ');

            return Event::NOTHING;
        }

        $target = $this->sync_manager->selectBlockInfo($netBlockInfo);
        $blockInfo = $target['item'];
        // $unique = $target['unique'];
        //
        // if ($unique === false) {
        //    # fork 인지 ;; 정보 기록 필요함.
        //    # 필요에 따라서 네트워크 ban;
        // }

        $host = $blockInfo['host'];

        Property::subjectNode($this->subjectNodeByHost($aliveNodes, $host));

        $nextBlockhash = $blockInfo['blockhash'];
        $nextStandardTimestamp = $blockInfo['s_timestamp'];

        $transactions = $this->sync_manager->getBlockFile($host, $myRoundNumber);

        return $this->syncCommit($transactions, $lastBlock, $nextBlockhash, $nextStandardTimestamp);
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경
    public function syncBunch($aliveNodes, $myRoundNumber): string
    {
        $netBunchInfo = $this->sync_manager->netBunchInfo($aliveNodes, $myRoundNumber);

        if (count($netBunchInfo) === 0) {
            IMLog::add('[Sync] netBunchInfo false ');

            return Event::NOTHING;
        }

        $target = $this->sync_manager->selectBunchInfo($netBunchInfo);
        $this->logger->debug('bunch target', [$target]);
        $bunchInfo = $target['item'];
        // $unique = $target['unique'];
        //
        // if ($unique === false) {
        //    # fork 인지 ;; 정보 기록 필요함.
        //    # 필요에 따라서 네트워크 ban;
        // }

        $host = $bunchInfo['host'];
        Property::subjectNode($this->subjectNodeByHost($aliveNodes, $host));

        $nextBlockhash = $bunchInfo['blockhash'];
        $bunch = $this->sync_manager->getBunchFile($host, $myRoundNumber);
        $this->logger->debug('bunch', [$bunch]);

        if ($bunch === '') {
            // Todo: 해당 부분에서는 따로 값을 가져오지 않아도 되는가?
            IMLog::add('[Sync] getBunchFile false ');

            return Event::NO_RESULT;
        }

        $tempBunch = $this->sync_manager->makeTempBunch($bunch);
        $chunks = $this->sync_manager->bunchChunks($tempBunch);

        $first = true;

        foreach ($chunks as $chunk) {
            $lastBlock = Block::GetLastBlock();
            $transactions = Chunk::getChunk("{$tempBunch}/{$chunk}");
            unlink("{$tempBunch}/{$chunk}");

            $fileBlockhash = mb_substr($chunk, 0, 64);
            $fileStandardTimestamp = mb_substr($chunk, 64, mb_strpos($chunk, '.') - 64);

            // find first
            if ($first === true && $nextBlockhash !== $fileBlockhash) {
                continue;
            }

            $first = false;

            // commit-manager init
            $syncResult = $this->syncCommit($transactions, $lastBlock, $fileBlockhash, $fileStandardTimestamp);

            if ($syncResult === Event::DIFFERENT) {
                return $syncResult;
            }
        }

        return Event::SUCCESS;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 주석 제거
    public function syncCommit(array $transactions, array $lastBlock, string $expectBlockhash, int $expectStandardTimestamp): string
    {
        $lastStandardTimestamp = $lastBlock['s_timestamp'];
        $lastBlockhash = $lastBlock['blockhash'];

        // commit-manager init
        // merge & sort
        $completedTransactions = $this->commit_manager->orderedTransactions($transactions, $lastStandardTimestamp, $expectStandardTimestamp);
        $completedTransactions = $this->commit_manager->completeTransactions($completedTransactions);

        // make expect block info
        $txCount = count($completedTransactions);
        $myBlockhash = Merkle::MakeBlockHash($lastBlockhash, Merkle::MakeMerkleHash($completedTransactions), $expectStandardTimestamp);
        $expectBlock = $this->commit_manager->nextBlock($lastBlock, $expectBlockhash, $txCount, $expectStandardTimestamp);

        if ($expectBlockhash === $myBlockhash) {
            $this->commit_manager->commit($completedTransactions, $lastBlock, $expectBlock);
            $this->commit_manager->makeTransactionChunk($expectBlock, $transactions);

            // tracker
            $this->tracker_manager->GenerateTracker();

            // ok
            return Event::SUCCESS;
        }

        // banish
        IMLog::add('[Sync] myBlockhash : ' . $myBlockhash);
        IMLog::add('[Sync] expectBlockhash : ' . $expectBlockhash);
        IMLog::add('[Sync] syncCommit false ');

        return Event::DIFFERENT;
    }

    // TODO: 접근 지정자를 public 에서 private 으로 변경 및 주석 제거
    public function ban(): void
    {
        if ($this->isTimeToSeparation()) {
            // ban;
            $subjectNode = Property::subjectNode();
            Tracker::banHost($subjectNode['host']);
            $this->resetFailCount();

            return;
        }

        $this->increaseFailCount();
    }
}
