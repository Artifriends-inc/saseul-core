<?php

namespace Saseul\Custom\Transaction;

use Saseul\System\Key;
use Saseul\Version;

abstract class AbstractTransaction
{
    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;
    protected $type;
    protected $version;
    protected $from;
    protected $timestamp;

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

        $this->type = $this->transaction['type'] ?? '';
        $this->version = $this->transaction['version'] ?? '';
        $this->from = $this->transaction['from'] ?? '';
        $this->timestamp = $this->transaction['timestamp'] ?? '';
    }

    public function getValidity(): bool
    {
        $calledTransaction = new \ReflectionClass(get_class($this));

        return Version::isValid($this->version)
            && $this->type === $calledTransaction->getShortName()
            && is_numeric($this->timestamp)
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature(
                $this->thash,
                $this->public_key,
                $this->signature
            );
    }

    abstract public function loadStatus(): void;

    abstract public function getStatus(): void;

    abstract public function makeDecision(): string;

    abstract public function setStatus(): void;
}
