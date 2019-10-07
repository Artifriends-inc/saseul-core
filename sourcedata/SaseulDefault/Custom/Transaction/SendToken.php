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

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->to = $transaction['to'] ?? null;
        $this->token_name = $transaction['token_name'] ?? null;
        $this->amount = $transaction['amount'] ?? null;
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidTo()
            && $this->isValidAmount();
    }

    public function loadStatus(): void
    {
        Token::LoadToken($this->from, $this->token_name);
        Token::LoadToken($this->to, $this->token_name);
    }

    public function getStatus(): void
    {
        $this->from_token_balance = Token::GetBalance($this->from, $this->token_name);
        $this->to_token_balance = Token::GetBalance($this->to, $this->token_name);
    }

    public function makeDecision(): string
    {
        if ((int) $this->amount > (int) $this->from_token_balance) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function setStatus(): void
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
