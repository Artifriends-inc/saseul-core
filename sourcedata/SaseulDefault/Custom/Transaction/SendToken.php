<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Account;
use Saseul\Constant\Decision;
use Saseul\Custom\Status\Token;

class SendToken extends AbstractTransaction
{
    private $to;
    private $token_name;
    private $amount;
    private $from_token_balance;
    private $to_token_balance;

    /**
     * @deprecated
     * 기존의 _Init 메서드의 기능은 initialize 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * initialize 로 대체 되고 제거되어야 한다.
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

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->to = $transaction['to'] ?? null;
        $this->token_name = $transaction['token_name'] ?? null;
        $this->amount = $transaction['amount'] ?? null;
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

    public function loadStatus()
    {
        Token::LoadToken($this->from, $this->token_name);
        Token::LoadToken($this->to, $this->token_name);
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

    public function getStatus()
    {
        $this->from_token_balance = Token::GetBalance($this->from, $this->token_name);
        $this->to_token_balance = Token::GetBalance($this->to, $this->token_name);
    }

    /**
     * @deprecated
     * 기존의 _MakeDecision 메서드의 기능은 makeDecision 메서드가 담당하며
     * 다른 모든 Transaction Api 가 AbstractTransaction 을 구현을 완료하면
     * makeDecision 로 대체 되고 제거되어야 한다.
     */
    public function _MakeDecision()
    {
        $this->makeDecision();
    }

    public function makeDecision()
    {
        if ((int) $this->amount > (int) $this->from_token_balance) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
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

    public function setStatus()
    {
        $this->from_token_balance = (int) $this->from_token_balance - (int) $this->amount;
        $this->to_token_balance = (int) $this->to_token_balance + (int) $this->amount;

        Token::SetBalance($this->from, $this->token_name, $this->from_token_balance);
        Token::SetBalance($this->to, $this->token_name, $this->to_token_balance);
    }

    private function isValidTo(): bool
    {
        return is_string($this->to)
            && (mb_strlen($this->to) === Account::ADDRESS_SIZE);
    }

    private function isValidAmount(): bool
    {
        return is_numeric($this->amount)
            && ((int) $this->amount > 0);
    }
}
