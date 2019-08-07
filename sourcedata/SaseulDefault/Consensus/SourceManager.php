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

    public function getSource($host, $blockNumber)
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

    public function makeSourceArchive($sourceArchive, $sourceHash)
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
        usleep(10000);

        return "{$sourceFullPath}";
    }

    public function changeSourceFolder($sourceFolder)
    {
        $saseulSource = Directory::SASEUL_SOURCE;

        // symlink;
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
