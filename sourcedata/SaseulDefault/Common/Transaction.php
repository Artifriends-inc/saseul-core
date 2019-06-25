<?php

namespace Saseul\Common;

use Saseul\Constant\Decision;

class Transaction
{
    public function _Init($transaction, $thash, $public_key, $signature)
    {
    }

    public function _GetValidity()
    {
        return false;
    }

    public function _LoadStatus()
    {
    }

    public function _GetStatus()
    {
    }

    public function _MakeDecision()
    {
        return Decision::REJECT;
    }

    public function _SetStatus()
    {
    }
}
