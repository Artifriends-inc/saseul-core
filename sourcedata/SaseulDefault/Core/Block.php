<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\DataAccess\Models\Block as BlockModel;
use Saseul\DataAccess\Models\Transaction as TransactionModel;
use Saseul\System\Database;
use Saseul\Util\DateTime;

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
