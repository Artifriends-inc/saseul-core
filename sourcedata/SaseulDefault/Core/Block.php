<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\Constant\Structure;
use Saseul\Models\Block as BlockModel;
use Saseul\Models\Transaction as TransactionModel;
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

    /**
     * 최근 Block 목록을 반환한다.
     *
     * @param int $max_count Get block max count (default: 100)
     *
     * @return array
     */
    public static function getLatestBlockList(int $max_count = 100): array
    {
        $filter = [];
        $option = [
            'sort' => ['timestamp' => MongoDb::DESC],
            'limit' => $max_count,
        ];

        return (new BlockModel())->find($filter, $option);
    }

    /**
     * 최근 Transaction 을 반환한다.
     *
     * @param int $max_count Get transaction max count (default: 100)
     *
     * @return array
     */
    public static function getLatestTransactionList(int $max_count = 100): array
    {
        $filter = [];
        $option = [
            'sort' => ['timestamp' => MongoDb::DESC],
            'limit' => $max_count,
        ];

        return (new TransactionModel())->find($filter, $option);
    }

    public static function txFileName(int $block_number)
    {
        $block = self::getBlockInfoByNumber($block_number);

        if (isset($block['blockhash'], $block['s_timestamp'])) {
            return $block['blockhash'] . $block['s_timestamp'];
        }

        return '';
    }

    /**
     * 입력한 Block number 를 가진 Block 정보를 반환한다.
     *
     * @param int $block_number Block number
     *
     * @return array
     */
    public static function getBlockInfoByNumber(int $block_number): array
    {
        $filter = ['block_number' => $block_number];

        return (new BlockModel())->findOne($filter);
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
        $option = ['sort' => ['timestamp' => MongoDb::DESC]];

        return (new BlockModel())->findOne([], $option);
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
