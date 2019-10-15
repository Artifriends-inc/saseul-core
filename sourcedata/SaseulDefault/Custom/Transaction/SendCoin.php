<?php

namespace Saseul\Custom\Transaction;

use Saseul\Constant\Account;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Fee;

class SendCoin extends AbstractTransaction
{
    private $to;
    private $amount;
    private $fee;

    private $from_balance;
    private $to_balance;
    private $coin_fee;

    public function initialize(
        $transaction,
        $thash,
        $public_key,
        $signature
    ): void {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->to = $transaction['to'] ?? null;
        $this->amount = $transaction['amount'] ?? null;
        $this->fee = $transaction['fee'] ?? null;
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidTo()
            && $this->isValidFee()
            && $this->isValidAmount();
    }

    public function loadStatus(): void
    {
        Coin::LoadBalance($this->from);
        Coin::LoadBalance($this->to);
    }

    public function getStatus(): void
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->to_balance = Coin::GetBalance($this->to);
        $this->coin_fee = Fee::GetFee();
    }

    public function makeDecision(): string
    {
        if ((int) $this->amount + (int) $this->fee > (int) $this->from_balance) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function setStatus(): void
    {
        $this->from_balance = (int) $this->from_balance - (int) $this->amount;
        $this->from_balance = (int) $this->from_balance - (int) $this->fee;
        $this->to_balance = (int) $this->to_balance + (int) $this->amount;
        $this->coin_fee = (int) $this->coin_fee + (int) $this->fee;

        Coin::SetBalance($this->from, $this->from_balance);
        Coin::SetBalance($this->to, $this->to_balance);
        Fee::SetFee($this->coin_fee);
    }

    private function isValidTo(): bool
    {
        return is_string($this->to)
            && (mb_strlen($this->to) === Account::ADDRESS_SIZE);
    }

    private function isValidFee(): bool
    {
        return is_numeric($this->fee)
            && ($this->fee >= 0);
    }

    private function isValidAmount(): bool
    {
        return is_numeric($this->amount)
            && ($this->amount > 0)
            && ((int) $this->amount <= Env::$genesis['coin_amount']);
    }
}
