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
    private $token_amount;
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

        $this->token_amount = $transaction['amount'] ?? null;
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
            && $this->isValidTokenAmount()
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
        return $this->makeDecision();
    }

    public function makeDecision()
    {
        // TODO: 필요한 조건문인지 확인 필요함
        if ($this->publish_token_info == []) {
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

        $total_amount = $total_amount + (int) $this->token_amount;
        $this->from_token_balance = (int) $this->from_token_balance + (int) $this->token_amount;
        $this->publish_token_info = [
            'publisher' => $this->from,
            'total_amount' => $total_amount,
        ];

        Token::SetBalance($this->from, $this->token_name, $this->from_token_balance);
        TokenList::SetInfo($this->token_name, $this->publish_token_info);
    }

    private function isValidTokenAmount(): bool
    {
        return is_numeric($this->token_amount)
            && ((int) $this->token_amount > 0)
            && ((int) $this->token_amount <= Env::$genesis['coin_amount']);
    }

    private function isValidTokenName(): bool
    {
        return is_string($this->token_name)
            && (mb_strlen($this->token_name) < 64);
    }

    // TODO: 실제로 존재하는 token_publisher인지 검사해야한다.
    private function isValidTokenPublisher(): bool
    {
        return is_string($this->token_publisher)
            && (mb_strlen($this->token_publisher) === Account::ADDRESS_SIZE);
    }
}
