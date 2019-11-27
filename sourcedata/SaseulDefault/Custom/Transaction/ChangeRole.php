<?php

namespace Saseul\Custom\Transaction;

use Saseul\Constant\Decision;
use Saseul\Constant\Role;
use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Coin;

class ChangeRole extends AbstractTransaction
{
    private $role;
    private $from_deposit;

    public function initialize(
        $transaction,
        $thash,
        $public_key,
        $signature
    ): void {
        parent::initialize($transaction, $thash, $public_key, $signature);

        $this->role = $transaction['role'] ?? null;
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidRole();
    }

    public function loadStatus(): void
    {
        Coin::loadDeposit($this->from);
    }

    public function getStatus(): void
    {
        $this->from_deposit = Coin::getDeposit($this->from);
    }

    public function makeDecision(): string
    {
        if (!Role::isExist($this->role)) {
            return Decision::REJECT;
        }

        if ($this->role === Role::SUPERVISOR && (int) $this->from_deposit < 100000000) {
            return Decision::REJECT;
        }

        if ($this->role === Role::VALIDATOR && (int) $this->from_deposit < 100000000000) {
            return Decision::REJECT;
        }

        if ($this->role === Role::ARBITER) {
            return Decision::REJECT;
        }

        return Decision::ACCEPT;
    }

    public function setStatus(): void
    {
        Attributes::SetRole($this->from, $this->role);
    }

    private function isValidRole(): bool
    {
        return $this->isNotNull()
            && is_string($this->role);
    }

    private function isNotNull(): bool
    {
        return ($this->role === null) === false;
    }
}
