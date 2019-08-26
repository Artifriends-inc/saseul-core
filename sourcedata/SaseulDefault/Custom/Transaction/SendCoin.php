<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
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

    /**
     * @deprecated
     * 기존의 _Init 메서드의 기능은 initialize 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * getValidity 로 대체 되고 제거되어야 한다.
     *
     * @param mixed $transaction
     * @param mixed $thash
     * @param mixed $public_key
     * @param mixed $signature
     */
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

        $this->to = $transaction['to'] ?? null;
        $this->amount = $transaction['amount'] ?? null;
        $this->fee = $transaction['fee'] ?? null;
    }

    /**
     * @deprecated
     * 기존의 _GetValidity 메서드의 기능은 getValidity 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * getValidity 로 대체 되고 제거되어야 한다.
     */
    public function _GetValidity(): bool
    {
        return $this->getValidity();
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidTo()
            && $this->isValidFee()
            && $this->isValidAmount();
    }

    /**
     * @deprecated
     * 기존의 _LoadStatus 메서드의 기능은 loadStatus 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * loadStatus 로 대체 되고 제거되어야 한다.
     */
    public function _LoadStatus()
    {
        $this->loadStatus();
    }

    /**
     * @deprecated
     * 기존의 _GetStatus 메서드의 기능은 getStatus 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * getStatus 로 대체 되고 제거되어야 한다.
     */
    public function _GetStatus()
    {
        $this->getStatus();
    }

    /**
     * @deprecated
     * 기존의 _MakeDecision 메서드의 기능은 makeDecision 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * makeDecision 로 대체 되고 제거되어야 한다.
     */
    public function _MakeDecision()
    {
        return $this->makeDecision();
    }

    /**
     * @deprecated
     * 기존의 _SetStatus 메서드의 기능은 setStatus 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * setStatus 로 대체 되고 제거되어야 한다.
     */
    public function _SetStatus()
    {
        $this->setStatus();
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

    public function makeDecision()
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
