<?php

namespace Saseul\Custom\Transaction;

use Saseul\Common\AbstractTransaction;
use Saseul\Constant\Decision;
use Saseul\Constant\Role;
use Saseul\Custom\Status\Attributes;
use Saseul\Custom\Status\Coin;

class ChangeRole extends AbstractTransaction
{
    private $role;
    private $from_deposit;

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

        $this->role = $transaction['role'] ?? null;
    }

    public function _GetValidity(): bool
    {
        return $this->getValidity();
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->isValidRole();
    }

    public function _LoadStatus()
    {
        $this->loadStatus();
    }

    public function loadStatus()
    {
        Coin::LoadDeposit($this->from);
    }

    public function _GetStatus()
    {
        $this->getStatus();
    }

    public function getStatus()
    {
        $this->from_deposit = Coin::GetDeposit($this->from);
    }

    public function _MakeDecision()
    {
        return $this->makeDecision();
    }

    public function makeDecision()
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

    public function _SetStatus()
    {
        $this->setStatus();
    }

    public function setStatus()
    {
        Attributes::SetRole($this->from, $this->role);
    }

    private function isValidRole(): bool
    {
        return $this->isNotNull($this->role)
            && is_string($this->role);
    }

    private function isNotNull($value): bool
    {
        return ($this->role === null) === false;
    }
}
