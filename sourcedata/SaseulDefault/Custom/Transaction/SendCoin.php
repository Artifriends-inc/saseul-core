<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\Transaction;
use Saseul\Constant\Account;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Fee;
use Saseul\System\Key;
use Saseul\Version;

class SendCoin extends Transaction
{
    public const TYPE = 'SendCoin';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $to;
    private $amount;
    private $fee;
    private $transactional_data;
    private $timestamp;

    private $from_balance;
    private $to_balance;
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
        if (isset($this->transaction['to'])) {
            $this->to = $this->transaction['to'];
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
            && is_string($this->to)
            && is_numeric($this->amount)
            && is_numeric($this->fee)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && (mb_strlen($this->to) === Account::ADDRESS_SIZE)
            && ((int) $this->amount <= Env::$genesis['coin_amount'])
            && ((int) $this->amount > 0)
            && ((int) $this->fee >= 0)
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        Coin::LoadBalance($this->from);
        Coin::LoadBalance($this->to);
    }

    public function _GetStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->to_balance = Coin::GetBalance($this->to);
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
        $this->from_balance = (int) $this->from_balance - (int) $this->amount;
        $this->from_balance = (int) $this->from_balance - (int) $this->fee;
        $this->to_balance = (int) $this->to_balance + (int) $this->amount;
        $this->coin_fee = (int) $this->coin_fee + (int) $this->fee;

        Coin::SetBalance($this->from, $this->from_balance);
        Coin::SetBalance($this->to, $this->to_balance);
        Fee::SetFee($this->coin_fee);
    }
}
