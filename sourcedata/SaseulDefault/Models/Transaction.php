<?php

namespace Saseul\Models;

use Saseul\System\Database;

class Transaction
{
    /** @var Database */
    private $db;

    /** @var string */
    private $transactionHash;

    /** @var int */
    private $timestamp;

    /** @var string */
    private $blockHash;

    /** @var string */
    private $publicKey;

    /** @var string */
    private $result;

    /** @var string */
    private $signature;

    /** @var array */
    private $transactionData;

    public function __construct()
    {
        $this->db = Database::getInstance();

        $this->transactionHash = '';
        $this->timestamp = 0;
        $this->blockHash = '';
        $this->publicKey = '';
        $this->result = '';
        $this->signature = '';
        $this->transactionData = [];
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getBlockHash(): string
    {
        return $this->blockHash;
    }

    public function getPublcKey(): string
    {
        return $this->publicKey;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getTransactionData(): array
    {
        return $this->transactionData;
    }

    /**
     * Transaction 하나의 데이터를 찾아서 반환한다.
     *
     * @param array $filter
     * @param array $option
     *
     * @return array
     */
    public function find(array $filter, array $option = []): array
    {
        $cursor = $this->db->getTransactionsCollection()->find($filter, $option);

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
        $cursor = $this->db->getTransactionsCollection()->findOne($filter, $option);

        $this->setAttributeUseObject($cursor);
        return $this->getArray();
    }

    /**
     * Object로 입력받은 attribute를 설정합니다.
     *
     * @param object $attribute
     */
    private function setAttributeUseObject(object $attribute): void
    {
        $this->transactionHash = $attribute->thash;
        $this->timestamp = $attribute->timestamp;
        $this->blockHash = $attribute->block;
        $this->publicKey = $attribute->public_key;
        $this->result = $attribute->result;
        $this->signature = $attribute->signature;
        $this->transactionData = (array) $attribute->transaction;
    }

    /**
     * Attribute 값들을 array로 반환합니다.
     *
     * @return array
     */
    private function getArray(): array
    {
        return [
            'thash' => $this->transactionHash,
            'timestamp' => $this->timestamp,
            'block' => $this->blockHash,
            'public_key'=> $this->publicKey,
            'result' => $this->result,
            'signature' => $this->signature,
            'transaction' => $this->transactionData,
        ];
    }
}
