<?php

namespace Saseul\Consensus;

use Saseul\Constant\Directory;
use Saseul\Constant\Structure;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class SourceManager
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

    /**
     * @param $host
     * @param $blockNumber
     *
     * @return string
     *
     * @todo 빈 string 보다는 다른 값을 넣어 반환하는 것이 좋지 않을까?
     */
    public function getSource($host, $blockNumber): string
    {
        $urlGz = "http://{$host}/source?block_number={$blockNumber}";
        $tmpGz = Directory::TMP_SOURCE;
        file_put_contents($tmpGz, file_get_contents($urlGz));

        if (mime_content_type($tmpGz) === 'application/x-gzip') {
            return $tmpGz;
        }
        unlink($tmpGz);

        return '';
    }

    /**
     * 받아온 Source 파일을 Saseul<source hash> 폴더에 푼다.
     *
     * @param $sourceArchive
     * @param $sourceHash
     *
     * @return string
     */
    public function restoreSource(string $sourceArchive, string $sourceHash): string
    {
        $sourceFolder = Directory::SOURCE;
        $sourceFullPath = "{$sourceFolder}/Saseul{$sourceHash}";

        if (is_dir($sourceFullPath)) {
            return $sourceFullPath;
        }

        mkdir($sourceFullPath);
        chmod($sourceFullPath, 0775);

        $cmd = "tar -xvzf {$sourceArchive} -C {$sourceFullPath} ";
        shell_exec($cmd);
        // Todo: 10 초 이상 걸리면??
        usleep(10000);

        return $sourceFullPath;
    }

    /**
     * Source 폴더를 변경한다.
     *
     * @param $sourceFolder
     */
    public function changeSourceFolder($sourceFolder)
    {
        $saseulSource = Directory::SASEUL_SOURCE;

        // symlink
        if (file_exists($saseulSource)) {
            unlink($saseulSource);
        }

        symlink($sourceFolder, $saseulSource);
    }

    public function netGenerationInfo($nodes, $myRoundNumber, $originBlockhash)
    {
        $generationInfos = [];
        $hosts = [];

        foreach ($nodes as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'generationinfo', ['block_number' => $myRoundNumber]);

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            if (!TypeChecker::StructureCheck(Structure::API_GENERATION_INFO, $r)) {
                continue;
            }

            if ($r['data']['file_exists'] === false ||
                $r['data']['origin_blockhash'] !== $originBlockhash) {
                continue;
            }

            $generationInfos[] = [
                'host' => $item['host'],
                'exec_time' => $item['exec_time'],
                'origin_blockhash' => $r['data']['origin_blockhash'],
                'final_blockhash' => $r['data']['final_blockhash'],
                'source_hash' => $r['data']['source_hash'],
                'source_version' => $r['data']['source_version'],
            ];
        }

        return $generationInfos;
    }

    public function collectSourcehashs($netGenerationInfo, $targetInfo)
    {
        $sourceHashs = [];

        foreach ($netGenerationInfo as $generationInfo) {
            if ($generationInfo['source_version'] === $targetInfo['source_version']) {
                $sourceHashs[] = $generationInfo['source_hash'];
            }
        }

        return array_values(array_unique($sourceHashs));
    }

    public function selectGenerationInfo($bunchInfos)
    {
        return TypeChecker::findMostItem($bunchInfos, 'source_version');
    }
}
