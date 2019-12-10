<?php

namespace Saseul\DataAccess\Models;

class Block
{
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
     * Object로 입력받은 attribute를 설정합니다.
     *
     * @param object $attribute
     */
    public function setAttributeUseObject(object $attribute): void
    {
        $this->blockNumber = $attribute->block_number ?? $this->blockNumber;
        $this->lastBlockHash = $attribute->last_blockhash ?? $this->lastBlockHash;
        $this->blockHash = $attribute->blockhash ?? $this->blockHash;
        $this->transactionCount = $attribute->transaction_count ?? $this->transactionCount;
        $this->standardTimestamp = $attribute->s_timestamp ?? $this->standardTimestamp;
        $this->timestamp = $attribute->timestamp ?? $this->timestamp;
    }

    /**
     * Attribute 값들을 array로 반환합니다.
     *
     * @return array
     */
    public function getArray(): array
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
