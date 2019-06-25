<?php

namespace Saseul\Custom\Transaction;

use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Coin;
use Saseul\Constant\Decision;
use Saseul\System\Key;
use Saseul\Constant\Role;
use Saseul\Common\Transaction;
use Saseul\Version;

class ChangeRole extends Transaction
{
    public const TYPE = 'ChangeRole';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $role;
    private $transactional_data;
    private $timestamp;

    private $from_deposit;

    public function _Init($transaction, $thash, $public_key, $signature)
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        if (isset($this->transaction['type'])) {
            $this->type = $this->transaction['type'];
        }
        if (isset($this->transaction['version'])) {
            $this->version = $this->transaction['version'];
        }
        if (isset($this->transaction['from'])) {
            $this->from = $this->transaction['from'];
        }
        if (isset($this->transaction['role'])) {
            $this->role = $this->transaction['role'];
        }
        if (isset($this->transaction['transactional_data'])) {
            $this->transactional_data = $this->transaction['transactional_data'];
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }
    }

    public function _GetValidity(): bool
    {
        return Version::isValid($this->version)
            && is_string($this->role)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        Coin::LoadDeposit($this->from);
    }

    public function _GetStatus()
    {
        $this->from_deposit = Coin::GetDeposit($this->from);
    }

    public function _MakeDecision()
    {
        if (!Role::isExist($this->role)) {
            return Decision::REJECT;
        }

        if ($this->role === Role::SUPERVISOR && (int) $this->from_deposit < 100000000) {
            return Decision::REJECT;
        }

        if ($this->role === Role::VALIDATOR && (int) $this->from_deposit < 100000000000) {
            return Decision::REJECT;
        }

        if ($this->role === Role::ARBITER) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function _SetStatus()
    {
        Attributes::SetRole($this->from, $this->role);
    }
}
