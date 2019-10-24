<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\Constant\Structure;
use Saseul\System\Database;
use Saseul\Util\File;
use Saseul\Util\Logger;
use Saseul\Util\Merkle;
use Saseul\Util\Parser;
use Saseul\Util\TypeChecker;
use Saseul\Version;

class Generation
{
    /**
     * @param string $originBlockhash
     * @param int    $originBlockNumber
     * @param int    $finalBlockNumber
     * @param string $sourceHash
     * @param string $sourceVersion
     *
     * @throws Exception
     */
    public static function add(string $originBlockhash, int $originBlockNumber, int $finalBlockNumber, string $sourceHash, string $sourceVersion): void
    {
        $generation = [
            'origin_blockhash' => $originBlockhash,
            'origin_block_number' => $originBlockNumber,
            'final_blockhash' => '',
            'final_block_number' => $finalBlockNumber,
            'source_hash' => $sourceHash,
            'source_version' => $sourceVersion,
        ];

        static::update($generation);
    }

    /**
     * 종착을 짓는다.
     *
     * @param string $originBlockhash
     * @param string $finalBlockhash
     * @param string $sourceHash
     * @param string $sourceVersion
     *
     * @throws Exception
     */
    public static function finalize(string $originBlockhash, string $finalBlockhash, string $sourceHash, string $sourceVersion): void
    {
        $generation = [
            'origin_blockhash' => $originBlockhash,
            'final_blockhash' => $finalBlockhash,
            'source_hash' => $sourceHash,
            'source_version' => $sourceVersion,
        ];

        static::update($generation);
    }

    /**
     * generation 정보를 업데이트하거나 추가한다.
     *
     * @param array $generation
     *
     * @throws Exception
     */
    public static function update(array $generation): void
    {
        $db = Database::getInstance();

        if (!TypeChecker::StructureCheck(Structure::GENERATION, $generation)) {
            static::log()->err('invalid generation structure. finalization failed.');

            return;
        }

        $filter = ['origin_blockhash' => $generation['origin_blockhash']];
        $db->getGenerationsCollection()->updateOne(
            $filter,
            ['$set' => $generation],
            ['upsert' => true]
        );
    }

    public static function getItem($query = [])
    {
        $db = Database::getInstance();

        $opt = ['sort' => ['origin_block_number' => -1]];
        $rs = $db->Query(MongoDb::NAMESPACE_GENERATION, $query, $opt);

        $generation = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);

            $generation = [
                'origin_blockhash' => $item['origin_blockhash'] ?? '',
                'origin_block_number' => $item['origin_block_number'] ?? 0,
                'final_blockhash' => $item['final_blockhash'] ?? '',
                'final_block_number' => $item['final_block_number'] ?? 0,
                'source_hash' => $item['source_hash'] ?? '',
                'source_version' => $item['source_version'] ?? '',
            ];

            break;
        }

        return $generation;
    }

    /**
     * 현재 generation 값을 가져온다.
     *
     * @return array
     */
    public static function current(): array
    {
        if (self::getItem([]) === []) {
            self::add('', 0, (Rule::GENERATION - 1), Property::sourceHash(), Property::sourceVersion());
        }

        return self::getItem([]);
    }

    public static function generationByNumber(int $originBlockNumber)
    {
        return self::getItem(['origin_block_number' => $originBlockNumber]);
    }

    /**
     * Source Dir 을 압축 파일로 만든다.
     */
    public static function archiveSource(): void
    {
        $target = Directory::SASEUL_SOURCE;

        if (!is_dir($target)) {
            return;
        }

        $allFiles = File::getAllfiles($target);
        $allFilehashs = array_map('sha1_file', $allFiles);
        $sourceHash = Merkle::MakeMerkleHash($allFilehashs);
        $sourceFile = Directory::TAR_SOURCE_DIR . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";

        Property::sourceHash($sourceHash);
        Property::sourceVersion(Version::CURRENT);

        $generation = self::current();
        self::finalize($generation['origin_blockhash'], $generation['final_blockhash'], $sourceHash, Version::CURRENT);

        if (is_file($sourceFile)) {
            return;
        }

        $cmd = "tar -cvzf {$sourceFile} -C {$target} . ";
        shell_exec($cmd);
    }

    /**
     * @throws Exception
     *
     * @return \Monolog\Logger
     */
    private static function log(): \Monolog\Logger
    {
        return Logger::getLogger(Logger::DAEMON);
    }
}
