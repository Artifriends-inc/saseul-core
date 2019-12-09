<?php

namespace Saseul\DataAccess\Models;

use Saseul\System\Database;

class Block
{
    /** @var Database */
    private $db;

    /** @var int */
    private $blockNumber;

    /** @var string Previous block hash */
    private $lastBlockHash;

    /** @var string */
    private $blockHash;

    /** @var int */
    private $transactionCount;

    /** @var int Standard timestamp */
    private $standardTimestamp;

    /** @var int Timestamp */
    private $timestamp;

    public function __construct()
    {
        $this->db = Database::getInstance();

        $this->blockNumber = 0;
        $this->lastBlockHash = '';
        $this->blockHash = '';
        $this->transactionCount = 0;
        $this->standardTimestamp = 0;
        $this->timestamp = 0;
    }

    public function getBlockNumber(): int
    {
        return $this->blockNumber;
    }

    public function getLastBlockHash(): string
    {
        return $this->lastBlockHash;
    }

    public function getBlockHash(): string
    {
        return $this->blockHash;
    }

    public function getTransactionCount(): int
    {
        return $this->transactionCount;
    }

    public function getStandardTimestamp(): int
    {
        return $this->standardTimestamp;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Block 하나의 데이터를 찾아서 반환한다.
     *
     * @param array $filter
     * @param array $option
     *
     * @return array
     */
    public function find(array $filter, array $option = []): array
    {
        $cursor = $this->db->getBlocksCollection()->find($filter, $option);

        $list = [];
        foreach ($cursor as $item) {
            $this->setAttributeUseObject($item);
            $list[] = $this->getArray();
        }

        return $list;
    }

    /**
     * Filter로 select한 데이터들의 목록을 반환한다.
     *
     * @param array $filter
     * @param array $option
     *
     * @return array
     */
    public function findOne(array $filter, array $option = []): array
    {
        $cursor = $this->db->getBlocksCollection()->findOne($filter, $option);

        if ($cursor !== null) {
            $this->setAttributeUseObject($cursor);
        }

        return $this->getArray();
    }

    /**
     * Object로 입력받은 attribute를 설정합니다.
     *
     * @param object $attribute
     */
    private function setAttributeUseObject(object $attribute): void
    {
        $this->blockNumber = $attribute->block_number;
        $this->lastBlockHash = $attribute->last_blockhash;
        $this->blockHash = $attribute->blockhash;
        $this->transactionCount = $attribute->transaction_count;
        $this->standardTimestamp = $attribute->s_timestamp;
        $this->timestamp = $attribute->timestamp;
    }

    /**
     * Attribute 값들을 array로 반환합니다.
     *
     * @return array
     */
    private function getArray(): array
    {
        return [
            'block_number' => $this->blockNumber,
            'last_blockhash' => $this->lastBlockHash,
            'blockhash' => $this->blockHash,
            'transaction_count'=> $this->transactionCount,
            's_timestamp' => $this->standardTimestamp,
            'timestamp' => $this->timestamp
        ];
    }
}
