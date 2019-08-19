<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\Transaction;
use Saseul\Constant\Decision;
use Saseul\Constant\Role;
use Saseul\Core\Block;
use Saseul\Core\Env;
use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Coin;
use Saseul\System\Key;
use Saseul\Version;

class Genesis extends Transaction
{
    public const TYPE = 'Genesis';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $amount;
    private $transactional_data;
    private $timestamp;

    private $from_balance;
    private $from_role;
    private $block_count;

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
        if (isset($this->transaction['amount'])) {
            $this->amount = $this->transaction['amount'];
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
            && is_numeric($this->amount)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && ((int) $this->amount <= Env::$genesis['coin_amount'])
            && ((int) $this->amount > 0)
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        Coin::LoadBalance($this->from);
        Attributes::LoadRole($this->from);
    }

    public function _GetStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_role = Attributes::GetRole($this->from);
        $this->block_count = Block::getCount();
    }

    public function _MakeDecision()
    {
        if ($this->from !== Env::$genesis['address'] || (int) $this->block_count > 0) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function _SetStatus()
    {
        $this->from_balance = (int) $this->from_balance + (int) $this->amount;
        $this->from_role = Role::VALIDATOR;

        Coin::SetBalance($this->from, $this->from_balance);
        Attributes::SetRole($this->from, $this->from_role);
    }
}
