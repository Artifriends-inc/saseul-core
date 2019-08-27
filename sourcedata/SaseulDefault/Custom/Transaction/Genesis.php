<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Decision;
use Saseul\Constant\Role;
use Saseul\Core\Block;
use Saseul\Core\Env;
use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Coin;

class Genesis extends AbstractTransaction
{
    private $coin_amount;
    private $from_balance;
    private $from_role;
    private $block_count;

    public function _Init($transaction, $thash, $public_key, $signature)
    {
        $this->initialize($transaction, $thash, $public_key, $signature);
    }

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->coin_amount = $transaction['amount'] ?? null;
    }

    public function _GetValidity(): bool
    {
        return $this->getValidity();
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidCoinAmount();
    }

    public function _LoadStatus()
    {
        $this->loadStatus();
    }

    public function loadStatus()
    {
        Coin::LoadBalance($this->from);
        Attributes::LoadRole($this->from);
    }

    public function _GetStatus()
    {
        $this->getStatus();
    }

    public function getStatus()
    {
        $this->from_balance = Coin::GetBalance($this->from);
        $this->from_role = Attributes::GetRole($this->from);
        $this->block_count = Block::getCount();
    }

    public function _MakeDecision()
    {
        $this->makeDecision();
    }

    public function makeDecision()
    {
        if ($this->from !== Env::$genesis['address'] || (int) $this->block_count > 0) {
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
        $this->from_balance = (int) $this->from_balance + (int) $this->coin_amount;
        $this->from_role = Role::VALIDATOR;

        Coin::SetBalance($this->from, $this->from_balance);
        Attributes::SetRole($this->from, $this->from_role);
    }

    private function isValidCoinAmount(): bool
    {
        return $this->isNotNull($this->coin_amount)
            && is_numeric($this->coin_amount)
            && ((int) $this->coin_amount > 0)
            && ((int) $this->coin_amount <= Env::$genesis['coin_amount']);
    }

    private function isNotNull($source): bool
    {
        return ($source === null) === false;
    }
}
