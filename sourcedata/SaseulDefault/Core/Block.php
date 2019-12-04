<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\Constant\Structure;
use Saseul\System\Database;
use Saseul\Util\DateTime;
use Saseul\Util\Parser;

class Block
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function genesisHash(): string
    {
        return hash('sha256', json_encode(Env::$genesis['key']));
    }

    public static function generationOriginNumber(int $roundNumber)
    {
        $originNumber = ($roundNumber - ($roundNumber % Rule::GENERATION) - 1);

        if ($originNumber < 0) {
            $originNumber = 0;
        }

        return $originNumber;
    }

    public static function bunchFinalNumber(int $roundNumber)
    {
        return $roundNumber - ($roundNumber % Rule::BUNCH) + Rule::BUNCH - 1;
    }

    public static function lastBlock(): array
    {
        $opt = ['sort' => ['timestamp' => -1]];

        return self::data(MongoDb::NAMESPACE_BLOCK, [], $opt);
    }

    public static function nextBlock(array $lastBlock, string $blockhash, int $txCount, int $standardTimestamp): array
    {
        return [
            'block_number' => ((int) $lastBlock['block_number'] + 1),
            'last_blockhash' => $lastBlock['blockhash'],
            'blockhash' => $blockhash,
            'transaction_count' => $txCount,
            's_timestamp' => $standardTimestamp,
            'timestamp' => DateTime::Microtime(),
        ];
    }

    public static function lastBlocks(int $max_count = 100): array
    {
        $opt = ['sort' => ['timestamp' => -1]];

        return self::datas(MongoDb::NAMESPACE_BLOCK, $max_count, [], $opt);
    }

    public static function blockByNumber(int $block_number): array
    {
        $query = ['block_number' => $block_number];

        return self::data(MongoDb::NAMESPACE_BLOCK, $query);
    }

    public static function data(string $namespace, array $query = [], array $opt = []): array
    {
        $block = Structure::BLOCK;

        $blocks = self::datas($namespace, 1, $query, $opt);

        if (isset($blocks[0])) {
            $block = $blocks[0];
        }

        return $block;
    }

    public static function datas(string $namespace, int $max_count, array $query = [], array $opt = []): array
    {
        $db = Database::getInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);

            $datas[] = [
                'block_number' => (int) $item['block_number'] ?? 0,
                'last_blockhash' => $item['last_blockhash'] ?? '',
                'blockhash' => $item['blockhash'] ?? '',
                'transaction_count' => (int) $item['transaction_count'] ?? 0,
                's_timestamp' => (int) $item['s_timestamp'] ?? 0,
                'timestamp' => (int) $item['timestamp'] ?? 0,
            ];

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }

    public static function GetLastBlocks(int $max_count = 100): array
    {
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        return self::GetDatas(MongoDb::NAMESPACE_BLOCK, $max_count, $query, $opt);
    }

    public static function GetLastTransactions(int $max_count = 100): array
    {
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        return self::GetDatas(MongoDb::NAMESPACE_TRANSACTION, $max_count, $query, $opt);
    }

    public static function GetDatas(string $namespace, int $max_count, array $query = [], array $opt = []): array
    {
        $db = Database::getInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $data = Parser::objectToArray($item);
            unset($data['_id']);
            $datas[] = $data;

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }

    public static function txFileName(int $block_number)
    {
        $block = self::GetBlockByNumber($block_number);

        if (isset($block['blockhash'], $block['s_timestamp'])) {
            return $block['blockhash'] . $block['s_timestamp'];
        }

        return '';
    }

    public static function GetBlockByNumber(int $block_number)
    {
        $query = ['block_number' => $block_number];
        $blocks = self::GetDatas(MongoDb::NAMESPACE_BLOCK, 1, $query);

        if (isset($blocks[0])) {
            return $blocks[0];
        }

        return Structure::BLOCK;
    }

    /**
     * 마지막 블록에 대한 정보를 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getLastBlock(): array
    {
        $option = ['sort' => ['timestamp' => -1]];
        $cursor = (new self())->db->getBlocksCollection()->findOne([], $option);

        return [
            'block_number' => $cursor->block_number ?? 0,
            'last_blockhash' => $cursor->last_blockhash ?? '',
            'blockhash' => $cursor->blockhash ?? '',
            'transaction_count' => $cursor->transaction_count ?? 0,
            's_timestamp' => $cursor->s_timestamp ?? 0,
            'timestamp' => $cursor->timestamp ?? 0,
        ];
    }

    /**
     * Block 총수를 가져온다.
     *
     * @throws Exception
     *
     * @return int
     */
    public static function getCount(): int
    {
        return (new self())->db->getBlocksCollection()->countDocuments();
    }
}
