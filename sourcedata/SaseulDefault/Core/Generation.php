<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\Directory;
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
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

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

    /**
     * 현재 generation 값을 가져온다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function current(): array
    {
        if ((new self())->getInfo() === null) {
            self::add('', 0, (Rule::GENERATION - 1), Property::sourceHash(), Property::sourceVersion());
        }

        return (new self())->getInfo();
    }

    /**
     * origin block number를 이용하여 generation 정보를 반환한다.
     *
     * @param int $originBlockNumber 찾고자하는 block number
     *
     * @throws Exception
     *
     * @return array
     */
    public static function generationByNumber(int $originBlockNumber): array
    {
        return (new self())->getInfo($originBlockNumber);
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

    /**
     * Generation 데이터를 가져온다.
     *
     * @param null|int $originBlockNumber
     *
     * @throws Exception
     *
     * @return null|array
     */
    private function getInfo(int $originBlockNumber = null): ?array
    {
        $filter = [];
        if ($originBlockNumber !== null) {
            $filter += ['origin_block_number' => $originBlockNumber];
        }

        $option = ['sort' => ['origin_block_number' => -1]];
        $cursor = $this->db->getGenerationsCollection()->findOne($filter, $option);
        if ($cursor === null) {
            return null;
        }

        $item = Parser::objectToArray($cursor);

        return [
            'origin_blockhash' => $item['origin_blockhash'] ?? '',
            'origin_block_number' => $item['origin_block_number'] ?? 0,
            'final_blockhash' => $item['final_blockhash'] ?? '',
            'final_block_number' => $item['final_block_number'] ?? 0,
            'source_hash' => $item['source_hash'] ?? '',
            'source_version' => $item['source_version'] ?? ''
        ];
    }
}
