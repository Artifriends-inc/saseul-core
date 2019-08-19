<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Rule;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\File;
use Saseul\Util\Merkle;

class Chunk
{
    public static function txFromApiChunk(int $minTime, int $maxTime, int $maxCount = 300): array
    {
        $txs = [];
        $keys = [];
        $chunks = self::chunkList(Directory::API_CHUNKS);

        sort($chunks);

        foreach ($chunks as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $time = (int) (pathinfo($filePath)['filename']);

            if ($maxTime >= $time && $time > $minTime) {
                $contents = '[' . preg_replace('/\,*?$/', '', file_get_contents($filePath)) . ']';
                $contents = json_decode($contents, true);

                foreach ($contents as $content) {
                    if (in_array($content['signature'], $keys)) {
                        continue;
                    }

                    $txs[] = $content;
                    $keys[] = $content['signature'];
                }

                if (count($txs) >= $maxCount) {
                    break;
                }
            }
        }

        return array_slice($txs, 0, $maxCount);
    }

    public static function broadcastChunk(string $fileName): array
    {
        $broadcastChunk = [];

        $chunks = self::chunkList(Directory::BROADCAST_CHUNKS);

        foreach ($chunks as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $item = pathinfo($filePath)['filename'];

            if ($item === $fileName) {
                $contents = file_get_contents($filePath);
                $broadcastChunk = json_decode($contents, true);

                if (empty($broadcastChunk)) {
                    $broadcastChunk = [];
                }

                break;
            }
        }

        return $broadcastChunk;
    }

    public static function broadcastCode(int $timestamp)
    {
        $validatorAddress = Tracker::GetValidatorAddress();
        $collectedAddress = [];
        $broadcastCode = '';

        sort($validatorAddress);

        foreach (scandir(Directory::BROADCAST_CHUNKS) as $item) {
            if (preg_match("/{$timestamp}\\.json$/", $item)) {
                $address = preg_replace("/_{$timestamp}\\.json/", '', $item);
                $collectedAddress[] = $address;
            }
        }

        foreach ($validatorAddress as $address) {
            if (in_array($address, $collectedAddress)) {
                $broadcastCode .= '1';
            } else {
                $broadcastCode .= '0';
            }
        }

        return $broadcastCode;
    }

    public static function existsBroadcastChunkList(int $timestamp)
    {
        $files = [];
        $directory = Directory::BROADCAST_CHUNKS;

        foreach (scandir($directory) as $item) {
            if (preg_match("/{$timestamp}\\.json$/", $item)) {
                $files[] = pathinfo("{$directory}/{$item}")['filename'];
            }
        }

        return $files;
    }

    public static function chunkList(string $directory): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        foreach (scandir($directory) as $item) {
            if (preg_match('/\.json$/', $item)) {
                $files[] = "{$directory}/{$item}";
            }
        }

        return $files;
    }

    public static function contentSignature(string $key, int $timestamp, array $contents): string
    {
        $cache = Cache::GetInstance();
        $sig = $cache->get($key);

        if ($sig === false) {
            $sig = Key::makeSignature(Merkle::MakeMerkleHash($contents) . $timestamp, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());
            $cache->set($key, $sig, 10);
        }

        return $sig;
    }

    public static function isValidContentSignaure(string $publicKey, int $timestamp, string $signature, array $contents): bool
    {
        return Key::isValidSignature(Merkle::MakeMerkleHash($contents) . $timestamp, $publicKey, $signature);
    }

    public static function makeBroadcastChunk(string $fileName, string $publicKey, string $signature, array $transactions)
    {
        $fullPath = Directory::BROADCAST_CHUNKS . "/{$fileName}.json";
        $contents = [
            'public_key' => $publicKey,
            'content_signature' => $signature,
            'transactions' => $transactions
        ];

        file_put_contents($fullPath, json_encode($contents));
    }

    public static function GetExpectStandardTimestamp($last_s_timestamp, int $length = 5)
    {
        $expect_s_timestamp = 0;
        $max_s_timestamp = DateTime::Microtime() - Rule::MICROINTERVAL_OF_CHUNK;

        $d = scandir(Directory::API_CHUNKS);
        $file_timestamps = [];

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $dir) . '000000';

            if ((int) $file_timestamp > (int) $last_s_timestamp && (int) $file_timestamp < (int) $max_s_timestamp) {
                $file_timestamps[] = (int) $file_timestamp;
            }
        }

        sort($file_timestamps);

        for ($i = 0; $i < count($file_timestamps); $i++) {
            $expect_s_timestamp = $file_timestamps[$i];

            if ($i >= ($length - 1)) {
                break;
            }
        }

        return $expect_s_timestamp;
    }

    public static function RemoveAPIChunk(int $s_timestamp)
    {
        if (!is_numeric($s_timestamp)) {
            return;
        }

        $d = scandir(Directory::API_CHUNKS);
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match('/[0-9]+\\.json/', $dir)) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $dir) . '000000';

            if ((int) $file_timestamp <= (int) $s_timestamp) {
                $files[] = $dir;
            }
        }

        foreach ($files as $file) {
            $filename = Directory::API_CHUNKS . '/' . $file;
            unlink($filename);
        }
    }

    public static function RemoveBroadcastChunk($s_timestamp)
    {
        if (!is_numeric($s_timestamp)) {
            return;
        }

        $d = scandir(Directory::BROADCAST_CHUNKS);
        $match = [];
        $files = [];

        foreach ($d as $dir) {
            if (!preg_match('/([0-9]+)\\.(json|key)/', $dir, $match)) {
                continue;
            }

            if (!isset($match[1])) {
                continue;
            }

            $file_timestamp = preg_replace('/[^0-9]/', '', $match[1]) . '000000';

            if ((int) $file_timestamp <= (int) $s_timestamp) {
                $files[] = $dir;
            }
        }

        foreach ($files as $file) {
            $filename = Directory::BROADCAST_CHUNKS . '/' . $file;
            unlink($filename);
        }
    }

    public static function GetChunk($filename)
    {
        $file = fopen($filename, 'r');
        $contents = fread($file, filesize($filename));
        fclose($file);
        $contents = '[' . preg_replace('/\,*?$/', '', $contents) . ']';

        return json_decode($contents, true);
    }

    public static function txSubDir(int $block_number): string
    {
        $hex = str_pad(dechex($block_number), 12, '0', STR_PAD_LEFT);
        $dir = [
            mb_substr($hex, 0, 2),
            mb_substr($hex, 2, 2),
            mb_substr($hex, 4, 2),
            mb_substr($hex, 6, 2),
            mb_substr($hex, 8, 2),
        ];

        return implode('/', $dir);
    }

    public static function txFullDir(int $block_number): string
    {
        return Directory::TRANSACTIONS . '/' . self::txSubDir($block_number);
    }

    public static function txArchive(int $block_number): string
    {
        return Directory::TX_ARCHIVE . '/' . self::txSubDir($block_number) . '.tar.gz';
    }

    public static function makeTxSubDir(int $block_number): bool
    {
        $subdir = Directory::TRANSACTIONS;
        $dir = explode('/', self::txSubDir($block_number));
        $make = false;

        foreach ($dir as $item) {
            $subdir = $subdir . '/' . $item;

            if (!file_exists($subdir)) {
                mkdir($subdir);
                chmod($subdir, 0775);
                chown($subdir, getmyuid());
                chgrp($subdir, getmygid());
                $make = true;
            }
        }

        return $make;
    }

    public static function makeTxArchive($block_number)
    {
        $full_dir = self::txSubDir($block_number);
        $target = Directory::TRANSACTIONS . '/' . $full_dir;
        $output = Directory::TX_ARCHIVE . '/' . $full_dir . '.tar.gz';
        $subdir = Directory::TX_ARCHIVE;
        $dir = explode('/', $full_dir);

        array_pop($dir);

        if (!is_dir($target)) {
            return;
        }

        foreach ($dir as $item) {
            $subdir = $subdir . '/' . $item;

            if (!file_exists($subdir)) {
                mkdir($subdir);
                chmod($subdir, 0775);
                chown($subdir, getmyuid());
                chgrp($subdir, getmygid());
            }
        }

        if (!is_file($output)) {
            $targetDir = scandir($target);
            $files = [];

            foreach ($targetDir as $item) {
                if (preg_match('/\.json$/', $item)) {
                    $files[] = $item;
                }
            }

            if (count($files) === Rule::BUNCH || ($full_dir === '00/00/00/00/00' && count($files) === (Rule::BUNCH - 1))) {
                $cmd = "tar -cvzf {$output} -C {$target} . ";
                shell_exec($cmd);
            }
        }
    }

    public static function SaveBroadcastChunk($chunk)
    {
        $name = $chunk['name'];
        $rows = $chunk['rows'];
        $count = $chunk['count'];
        $signature = $chunk['signature'];
        $public_key = $chunk['public_key'];

        if (!preg_match('/[0-9]+\\.json$/', $name)) {
            return;
        }

        $filename = Directory::BROADCAST_CHUNKS . '/' . $name;
        $keyname = preg_replace('/\.json$/', '.key', $filename);

        if (is_file($filename) || is_file($keyname)) {
            if ($count !== count(file($filename))) {
                unlink($filename);
                unlink($keyname);
            }

            return;
        }

        $file = fopen($filename, 'a');
        foreach ($rows as $row) {
            fwrite($file, json_encode($row) . ",\n");
        }

        fclose($file);

        chmod($filename, 0775);

        $key = fopen($keyname, 'a');
        fwrite($key, $public_key . "\n");
        fwrite($key, $signature . "\n");
        fclose($key);

        chmod($keyname, 0775);
    }

    /**
     * 입력된 Transaction 데이터를 API chunk 폴더에 저장한다.
     *
     * @param $contents
     * @param $timestamp
     */
    public static function saveApiChunk($contents, $timestamp): void
    {
        $filename = Directory::API_CHUNKS . '/' . self::getId($timestamp) . '.json';

        $sign = false;

        if (!is_file($filename)) {
            $sign = true;
        }

        $file = fopen($filename, 'ab');
        fwrite($file, json_encode($contents) . ",\n");
        fclose($file);

        // Todo: 해당 부분은 dir 옵션으로 처리가 가능하다.
        if ($sign) {
            chmod($filename, 0775);
        }
    }

    /**
     * Transaction ID 값을 정의한다.
     *
     * @param $timestamp
     *
     * @return string|string[]|null
     */
    public static function getId($timestamp): ?string
    {
        $tid = $timestamp - ($timestamp % Rule::MICROINTERVAL_OF_CHUNK)
            + Rule::MICROINTERVAL_OF_CHUNK;

        return preg_replace('/0{6}$/', '', $tid);
    }

    public static function removeOldBlock(int $lastBlockNumber)
    {
        $db = Database::GetInstance();
        $lastGenerationNumber = Block::generationOriginNumber($lastBlockNumber);
        $lastBunchNumber = Block::generationOriginNumber($lastGenerationNumber) + Rule::BUNCH;

        do {
            $lastBunchNumber = Block::bunchFinalNumber($lastBunchNumber - Rule::BUNCH);
            File::rrmdir(self::txFullDir($lastBunchNumber));

            if (is_file(self::txArchive($lastBunchNumber))) {
                unlink(self::txArchive($lastBunchNumber));
            }
        } while ($lastBunchNumber > Rule::BUNCH);

        do {
            // 바로 이전 세대를 남기기 위해 한번 더 라스트로 이동;
            $lastGenerationNumber = Block::generationOriginNumber($lastGenerationNumber);
            $query = ['block_number' => ['$lt' => $lastGenerationNumber]];
            $blocks = Block::datas(MongoDbConfig::NAMESPACE_BLOCK, Rule::GENERATION, $query);

            $blockhashs = [];

            foreach ($blocks as $block) {
                $blockhashs[] = $block['blockhash'];
            }

            if (count($blockhashs) > 0) {
                $db->bulk->delete(['block' => ['$in' => $blockhashs]]);
                $db->BulkWrite(MongoDbConfig::NAMESPACE_TRANSACTION);

                $db->bulk->delete(['blockhash' => ['$in' => $blockhashs]]);
                $db->BulkWrite(MongoDbConfig::NAMESPACE_BLOCK);
            }
        } while ($lastGenerationNumber > Rule::GENERATION);
    }
}
