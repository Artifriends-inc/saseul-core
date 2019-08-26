<?php

namespace Saseul\Common;

use Saseul\Constant\Decision;

/**
 * @deprecated
 * Transaction 클래스를 상속하는 Api는 AbstractTransaction으로
 * 대체될 예정이며, 전체 Transaction Api가 AbstractTransaction을 상속할 때
 * 제거 되어야 한다.
 */
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
