<?php

namespace Saseul\Custom\Transaction;

use ReflectionClass;
use ReflectionException;
use Saseul\System\Key;
use Saseul\Version;

abstract class AbstractTransaction
{
    /** @var array Transaction data */
    protected $transaction;

    /** @var string Transaction hash */
    protected $thash;

    /** @var string Send Account public key */
    protected $public_key;

    /** @var string Transaction data signature */
    protected $signature;

    /** @var string Transaction type */
    protected $transactionType;

    /** @var string SASEUL core version */
    protected $version;

    /** @var string Send Account address */
    protected $from;

    /** @var string Timestamp of sending transaction. */
    protected $timestamp;

    /**
     * Transaction 을 초기화한다.
     *
     * @param array  $transaction Transaction data
     * @param string $thash       트랜젝션 Hash
     * @param string $public_key  Send tracker public key
     * @param string $signature   Transaction data signature
     */
    public function initialize(
        $transaction,
        $thash,
        $public_key,
        $signature
    ): void {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->transactionType = $this->transaction['type'] ?? '';
        $this->version = $this->transaction['version'] ?? '';
        $this->from = $this->transaction['from'] ?? '';
        $this->timestamp = $this->transaction['timestamp'] ?? '';
    }

    /**
     * Transaction data 를 검증한다.
     *
     * @throws ReflectionException
     *
     * @return bool
     */
    public function getValidity(): bool
    {
        $calledTransaction = new ReflectionClass(get_class($this));

        return Version::isValid($this->version)
            && $this->transactionType === $calledTransaction->getShortName()
            && is_numeric($this->timestamp)
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature(
                $this->thash,
                $this->public_key,
                $this->signature
            );
    }

    /**
     * Load status data.
     */
    abstract public function loadStatus(): void;

    /**
     * Get status data.
     */
    abstract public function getStatus(): void;

    /**
     * Make decision.
     *
     * @return string
     */
    abstract public function makeDecision(): string;

    /**
     * Set status data.
     */
    abstract public function setStatus(): void;
}
