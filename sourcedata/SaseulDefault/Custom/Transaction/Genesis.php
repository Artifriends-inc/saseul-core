<?php

namespace Saseul\Custom\Transaction;

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

    public function initialize($transaction, $thash, $public_key, $signature): void
    {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->coin_amount = $transaction['amount'] ?? null;
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidCoinAmount();
    }

    public function loadStatus(): void
    {
        Coin::loadBalance($this->from);
        Attributes::loadRole($this->from);
    }

    public function getStatus(): void
    {
        $this->from_balance = Coin::getBalance($this->from);
        $this->from_role = Attributes::getRole($this->from);
        $this->block_count = Block::getCount();
    }

    public function makeDecision(): string
    {
        if ($this->from !== Env::$genesis['address'] || (int) $this->block_count > 0) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function setStatus(): void
    {
        $this->from_balance = (int) $this->from_balance + (int) $this->coin_amount;
        $this->from_role = Role::VALIDATOR;

        Coin::setBalance($this->from, $this->from_balance);
        Attributes::setRole($this->from, $this->from_role);
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
