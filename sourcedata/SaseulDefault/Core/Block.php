<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\DataAccess\Models\Block as BlockModel;
use Saseul\DataAccess\Models\Transaction as TransactionModel;
use Saseul\System\Database;

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

        return (new self())->findBlock($filter, $option);
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

        return (new self())->findOneBlock($filter);
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

        return (new self())->findOneBlock([], $option);
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
     * Block 데이터 목록를 찾는다.
     *
     * @param array $filter
     * @param array $option
     *
     * @return array
     */
    private function findBlock(array $filter, array $option = []): array
    {
        $cursor = $this->db->getBlocksCollection()->find($filter, $option);

        $blockList = [];
        foreach ($cursor as $item) {
            $model = new BlockModel();
            $model->setAttributeUseObject($item);
            $blockList[] = $model->getArray();
        }

        return $blockList;
    }

    /**
     * 하나의 Block 데이터를 찾는다.
     *
     * @param array $filter
     * @param array $option
     *
     * @return array
     */
    private function findOneBlock(array $filter, array $option = []): array
    {
        $cursor = $this->db->getBlocksCollection()->findOne($filter, $option);

        $model = new BlockModel();
        $model->setAttributeUseObject((object) $cursor);

        return $model->getArray();
    }
}
