<?php

namespace Saseul\Custom\Transaction;

use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Fee;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\System\Key;
use Saseul\Common\Transaction;
use Saseul\Version;

class Withdraw extends Transaction
{
    public const TYPE = 'Withdraw';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $amount;
    private $fee;
    private $transactional_data;
    private $timestamp;

    private $from_balance;
    private $from_deposit;
    private $coin_fee;

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
        if (isset($this->transaction['fee'])) {
            $this->fee = $this->transaction['fee'];
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
            && is_numeric($this->fee)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && ((int) $this->amount <= Env::$genesis['coin_amount'])
            && ((int) $this->amount > 0)
            && ((int) $this->fee >= 0)
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        Coin::LoadBalance($this->from);
        Coin::LoadDeposit($this->from);
    }

    public function _GetStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_deposit = Coin::GetDeposit($this->from);
        $this->coin_fee = Fee::GetFee();
    }

    public function _MakeDecision()
    {
        if ((int) $this->amount + (int) $this->fee > (int) $this->from_balance) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function _SetStatus()
    {
        $this->from_deposit = (int) $this->from_deposit - (int) $this->amount;
        $this->from_deposit = (int) $this->from_deposit - (int) $this->fee;
        $this->from_balance = (int) $this->from_balance + (int) $this->amount;
        $this->coin_fee = (int) $this->coin_fee + (int) $this->fee;

        Coin::SetBalance($this->from, $this->from_balance);
        Coin::SetDeposit($this->from, $this->from_deposit);
        Fee::SetFee($this->coin_fee);
    }
}
