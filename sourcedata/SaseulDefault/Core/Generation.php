<?php

namespace Saseul\Core;

use Saseul\Version;
use Saseul\Util\TypeChecker;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Rule;
use Saseul\Constant\Structure;
use Saseul\System\Database;
use Saseul\Util\File;
use Saseul\Util\Merkle;
use Saseul\Util\Parser;

class Generation
{
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

        self::update($generation);
    }

    public static function finalize(string $originBlockhash, string $finalBlockhash, string $sourceHash, string $sourceVersion): void
    {
        $generation = [
            'origin_blockhash' => $originBlockhash,
            'final_blockhash' => $finalBlockhash,
            'source_hash' => $sourceHash,
            'source_version' => $sourceVersion,
        ];

        self::update($generation);
    }

    public static function update(array $generation): void
    {
        $db = Database::GetInstance();

        if (!TypeChecker::StructureCheck(Structure::GENERATION, $generation)) {
            IMLog::add('Error : invalid generation structure. finalization failed. ');
            return;
        }

        $filter = ['origin_blockhash' => $generation['origin_blockhash']];

        $db->bulk->update($filter, ['$set' => $generation], ['upsert' => true]);

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_GENERATION);
        }
    }

    public static function getItem($query = []) {
        $db = Database::GetInstance();

        $opt = ['sort' => ['origin_block_number' => -1]];
        $rs = $db->Query(MongoDbConfig::NAMESPACE_GENERATION, $query, $opt);

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

    public static function current(): array
    {
        if (self::getItem([]) === []) {
            self::add('', 0, (Rule::GENERATION - 1), Property::sourceHash(), Property::sourceVersion());
        }

        return self::getItem([]);
    }

    public static function generationByNumber(int $originBlockNumber) {
        return self::getItem(['origin_block_number' => $originBlockNumber]);
    }

    public static function makeSourceArchive()
    {
        $target = Directory::SASEUL_SOURCE;

        if (!is_dir($target)) {
            return;
        }

        $allFiles = File::getAllfiles($target);
        $allFilehashs = array_map('sha1_file', $allFiles);
        $sourceHash = Merkle::MakeMerkleHash($allFilehashs);
        $sourceFile = Directory::SOURCE . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";

        Property::sourceHash($sourceHash);
        Property::sourceVersion(Version::CURRENT);

        $generation = Generation::current();
        self::finalize($generation['origin_blockhash'], $generation['final_blockhash'], $sourceHash, Version::CURRENT);

        if (is_file($sourceFile)) {
            return;
        }

        $cmd = "tar -cvzf {$sourceFile} -C {$target} . ";
        shell_exec($cmd);
    }
}