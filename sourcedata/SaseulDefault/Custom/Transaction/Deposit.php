<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Fee;

// TODO: Deposit Api의 용도를 정확하게 파악하여 클래스 이름의 조정이 필요함
class Deposit extends AbstractTransaction
{
    private $coin_amount;
    private $fee;
    private $from_balance;
    private $from_deposit;
    private $coin_fee;

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->coin_amount = $transaction['amount'] ?? null;
        $this->fee = $transaction['fee'] ?? null;
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidCoinAmount()
            && $this->isValidFee();
    }

    public function loadStatus(): void
    {
        Coin::LoadBalance($this->from);
        Coin::LoadDeposit($this->from);
    }

    public function getStatus(): void
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_deposit = Coin::GetDeposit($this->from);
        $this->coin_fee = Fee::GetFee();
    }

    public function makeDecision(): string
    {
        if ((int) $this->coin_amount + (int) $this->fee > (int) $this->from_balance) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function setStatus(): void
    {
        $this->from_balance = (int) $this->from_balance - (int) $this->coin_amount;
        $this->from_balance = (int) $this->from_balance - (int) $this->fee;
        $this->from_deposit = (int) $this->from_deposit + (int) $this->coin_amount;
        $this->coin_fee = (int) $this->coin_fee + (int) $this->fee;

        Coin::SetBalance($this->from, $this->from_balance);
        Coin::SetDeposit($this->from, $this->from_deposit);
        Fee::SetFee($this->coin_fee);
    }

    private function isValidFee(): bool
    {
        return $this->isNotNull($this->fee)
            && is_numeric($this->fee)
            && ((int) $this->fee >= 0);
    }

    // TODO: Deposit API 가 정확한 용도에 따라 Coin Amount에 대한 추가 유효성 검사 필요
    // TODO: coin_amount에 대한 최대치 한도 적용 필요
    private function isValidCoinAmount(): bool
    {
        return $this->isNotNull($this->coin_amount)
            && is_numeric($this->coin_amount)
            && ((int) $this->coin_amount > 0)
            && ((int) $this->coin_amount <= Env::$genesis['coin_amount']);
    }

    private function isNotNull($value): bool
    {
        return ($value === null) === false;
    }
}
