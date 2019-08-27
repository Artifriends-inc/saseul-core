<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Fee;

class Withdraw extends AbstractTransaction
{
    private $withdrawal_amount;
    private $fee;
    private $from_balance;
    private $from_deposit;
    private $coin_fee;

    public function _Init($transaction, $thash, $public_key, $signature)
    {
        $this->initialize($transaction, $thash, $public_key, $signature);
    }

    public function initialize(
        $transaction,
        $thash,
        $public_key,
        $signature
    ): void {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->withdrawal_amount = $transaction['amount'] ?? null;
        $this->fee = $transaction['fee'] ?? null;
    }

    public function _GetValidity(): bool
    {
        return $this->getValidity();
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidWithdrawalAmount()
            && $this->isValidFee();
    }

    public function _LoadStatus()
    {
        $this->loadStatus();
    }

    public function loadStatus()
    {
        Coin::LoadBalance($this->from);
        Coin::LoadDeposit($this->from);
    }

    public function _GetStatus()
    {
        $this->getStatus();
    }

    public function getStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_deposit = Coin::GetDeposit($this->from);
        $this->coin_fee = Fee::GetFee();
    }

    public function _MakeDecision()
    {
        $this->makeDecision();
    }

    public function makeDecision()
    {
        if ((int) $this->withdrawal_amount + (int) $this->fee > (int) $this->from_deposit) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function _SetStatus()
    {
        $this->setStatus();
    }

    public function setStatus()
    {
        $this->from_deposit = (int) $this->from_deposit - (int) $this->withdrawal_amount;
        $this->from_deposit = (int) $this->from_deposit - (int) $this->fee;
        $this->from_balance = (int) $this->from_balance + (int) $this->withdrawal_amount;
        $this->coin_fee = (int) $this->coin_fee + (int) $this->fee;

        Coin::SetBalance($this->from, $this->from_balance);
        Coin::SetDeposit($this->from, $this->from_deposit);
        Fee::SetFee($this->coin_fee);
    }

    // TODO: Genesis의 Coin Amount와 비교하는게 맞는지 확인 필요
    private function isValidWithdrawalAmount(): bool
    {
        return is_numeric($this->withdrawal_amount)
            && ((int) $this->withdrawal_amount > 0)
            && ((int) $this->withdrawal_amount <= Env::$genesis['coin_amount']);
    }

    private function isValidFee(): bool
    {
        return is_numeric($this->fee)
            && ((int) $this->fee >= 0);
    }
}
