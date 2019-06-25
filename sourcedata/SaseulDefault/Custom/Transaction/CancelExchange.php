<?php

namespace Saseul\Custom\Transaction;

use Saseul\Constant\Decision;
use Saseul\System\Key;
use Saseul\Common\Transaction;
use Saseul\Version;

class CancelExchange extends Transaction
{
    public const TYPE = 'CancelExchange';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $eid;
    private $transactional_data;
    private $timestamp;

    private $related_order;

    public function _Init($transaction, $thash, $public_key, $signature)
    {
        $this->transaction = $transaction;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        if (isset($this->transaction['type'])) {
            $this->type = $this->transaction['type'];
        }
        if (isset($this->transaction['version'])) {
            $this->version = $this->transaction['version'];
        }
        if (isset($this->transaction['from'])) {
            $this->from = $this->transaction['from'];
        }
        if (isset($this->transaction['eid'])) {
            $this->eid = $this->transaction['eid'];
        }
        if (isset($this->transaction['transactional_data'])) {
            $this->transactional_data = $this->transaction['transactional_data'];
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }

        // from_amount * exchange_rate = to_amount;
    }

    public function _GetValidity(): bool
    {
        return Version::isValid($this->version)
            && is_string($this->eid)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        \Saseul\Custom\Status\Exchange::LoadOrderByEID($this->eid);
    }

    public function _GetStatus()
    {
        $this->related_order = \Saseul\Custom\Status\Exchange::GetOrderByEID($this->eid);
    }

    public function _MakeDecision()
    {
        if (isset($this->related_order['from'])) {
            if ($this->related_order['from'] === $this->from) {
                return Decision::ACCEPT;
            }
        }

        return Decision::REJECT;
    }

    public function _SetStatus()
    {
        \Saseul\Custom\Status\Exchange::SetDeleteOrder($this->eid);
    }
}
