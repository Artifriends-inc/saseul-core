<?php

namespace Saseul\DataAccess\Models;

class Transaction
{
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
        return (array) $this->transactionData;
    }

    /**
     * Object로 입력받은 attribute를 설정합니다.
     *
     * @param object $attribute
     */
    public function setAttributeUseObject(object $attribute): void
    {
        $this->transactionHash = $attribute->thash ?? $this->transactionHash;
        $this->timestamp = $attribute->timestamp ?? $this->timestamp;
        $this->blockHash = $attribute->block ?? $this->blockHash;
        $this->publicKey = $attribute->public_key ?? $this->publicKey;
        $this->result = $attribute->result ?? $this->result;
        $this->signature = $attribute->signature ?? $this->signature;
        $this->transactionData = $attribute->transaction ?? $this->transactionData;
    }

    /**
     * Attribute 값들을 array로 반환합니다.
     *
     * @return array
     */
    public function getArray(): array
    {
        return [
            'thash' => $this->transactionHash,
            'timestamp' => $this->timestamp,
            'block' => $this->blockHash,
            'public_key'=> $this->publicKey,
            'result' => $this->result,
            'signature' => $this->signature,
            'transaction' => (array) $this->transactionData,
        ];
    }
}
