<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Account;
use Saseul\Constant\Decision;
use Saseul\Core\Env;
use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Token;
use Saseul\Custom\Status\TokenList;

class CreateToken extends AbstractTransaction
{
    private $amount;
    private $token_name;
    private $token_publisher;
    private $from_role;
    private $publish_token_info;
    private $from_token_balance;

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

        $this->amount = $transaction['amount'] ?? null;
        $this->token_name = $transaction['token_name'] ?? null;
        $this->token_publisher = $transaction['token_publisher'] ?? null;
    }

    public function _GetValidity(): bool
    {
        return $this->getValidity();
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidAmount()
            && $this->isvalidTokenName()
            && $this->isValidTokenPublisher();
    }

    public function _LoadStatus()
    {
        $this->loadStatus();
    }

    public function loadStatus()
    {
        Token::LoadToken($this->from, $this->token_name);
        TokenList::LoadTokenList($this->token_name);
        Attributes::LoadRole($this->from);
    }

    public function _GetStatus()
    {
        $this->getStatus();
    }

    public function getStatus()
    {
        $this->from_token_balance = Token::GetBalance($this->from, $this->token_name);
        $this->publish_token_info = TokenList::GetInfo($this->token_name);
        $this->from_role = Attributes::GetRole($this->from);
    }

    public function _MakeDecision()
    {
        $this->makeDecision();
    }

    public function makeDecision()
    {
        if ($this->publish_token_info == []) {
//            if ($this->from_role === Role::VALIDATOR) {
//                return Decision::ACCEPT;
//            }
            return Decision::ACCEPT;
        }
        if (isset($this->publish_token_info['publisher'])
            && $this->publish_token_info['publisher'] === $this->from) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _SetStatus()
    {
        $this->setStatus();
    }

    public function setStatus()
    {
        $total_amount = 0;

        if (isset($this->publish_token_info['total_amount'])) {
            $total_amount = $this->publish_token_info['total_amount'];
        }

        $total_amount = $total_amount + (int) $this->amount;
        $this->from_token_balance = (int) $this->from_token_balance + (int) $this->amount;
        $this->publish_token_info = [
            'publisher' => $this->from,
            'total_amount' => $total_amount,
        ];

        Token::SetBalance($this->from, $this->token_name, $this->from_token_balance);
        TokenList::SetInfo($this->token_name, $this->publish_token_info);
    }

    private function isValidAmount(): bool
    {
        return is_numeric($this->amount)
            && ((int) $this->amount > 0)
            && ((int) $this->amount <= Env::$genesis['coin_amount']);
    }

    private function isValidTokenName(): bool
    {
        return is_string($this->token_name)
            && (mb_strlen($this->token_name) < 64);
    }

    private function isValidTokenPublisher(): bool
    {
        return is_string($this->token_publisher)
            && (mb_strlen($this->token_publisher) === Account::ADDRESS_SIZE);
    }
}
