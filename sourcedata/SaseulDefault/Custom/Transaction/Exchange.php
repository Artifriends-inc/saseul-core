<?php

namespace Saseul\Custom\Transaction;

use Saseul\Custom\Status\Coin;
use Saseul\Custom\Status\Token;
use Saseul\Custom\Status\TokenList;
use Saseul\Constant\Decision;
use Saseul\System\Key;
use Saseul\Common\Transaction;
use Saseul\Version;

class Exchange extends Transaction
{
    public const TYPE = 'Exchange';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $from_type;
    private $from_currency_name;
    private $from_amount;
    private $to_type;
    private $to_currency_name;
    private $to_amount;
    private $exchange_rate;
    private $exchange_type;
    private $transactional_data;
    private $timestamp;

    private $valid_token = false;
    private $balance;
    private $related_orders;

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
        if (isset($this->transaction['from_type'])) {
            $this->from_type = $this->transaction['from_type'];
        }
        if (isset($this->transaction['from_currency_name'])) {
            $this->from_currency_name = $this->transaction['from_currency_name'];
        }
        if (isset($this->transaction['from_amount'])) {
            $this->from_amount = $this->transaction['from_amount'];
        }
        if (isset($this->transaction['to_type'])) {
            $this->to_type = $this->transaction['to_type'];
        }
        if (isset($this->transaction['to_currency_name'])) {
            $this->to_currency_name = $this->transaction['to_currency_name'];
        }
        if (isset($this->transaction['to_amount'])) {
            $this->to_amount = $this->transaction['to_amount'];
        }
        if (isset($this->transaction['exchange_rate'])) {
            $this->exchange_rate = $this->transaction['exchange_rate'];
        }
        if (isset($this->transaction['exchange_type'])) {
            $this->exchange_type = $this->transaction['exchange_type'];
        }
        if (isset($this->transaction['transactional_data'])) {
            $this->transactional_data = $this->transaction['transactional_data'];
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }

        // to_amount * exchange_rate = from_amount;
    }

    public function _GetValidity(): bool
    {
        return Version::isValid($this->version)
            && is_string($this->from_type)
            && is_string($this->from_currency_name)
            && is_numeric($this->from_amount)
            && is_string($this->to_type)
            && is_string($this->to_currency_name)
            && is_numeric($this->to_amount)
            && is_numeric($this->exchange_rate)
            && is_string($this->exchange_type)
            && is_numeric($this->timestamp)
            && $this->type === self::TYPE
            && in_array($this->to_type, ['coin', 'token'])
            && in_array($this->from_type, ['coin', 'token'])
            && in_array($this->exchange_type, ['buy', 'sell'])
            && ((int) $this->from_amount > 0)
            && ((int) $this->to_amount > 0)
            && ((float) $this->exchange_rate > 0)
            && $this->isValidAmount()
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            if ($this->from_type === 'coin') {
                Coin::LoadBalance($this->from);
            }

            if ($this->from_type === 'token') {
                Token::LoadToken($this->from, $this->from_currency_name);
            }
        } else {
            // sell

            if ($this->to_type === 'coin') {
                Coin::LoadBalance($this->from);
            }

            if ($this->to_type === 'token') {
                Token::LoadToken($this->from, $this->to_currency_name);
            }
        }

        if ($this->from_type === 'token') {
            TokenList::LoadTokenList($this->from_currency_name);
        }

        if ($this->to_type === 'token') {
            TokenList::LoadTokenList($this->to_currency_name);
        }

        \Saseul\Custom\Status\Exchange::LoadOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name);
    }

    public function _GetStatus()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            if ($this->from_type === 'coin') {
                $this->balance = Coin::GetBalance($this->from);
            }

            if ($this->from_type === 'token') {
                $this->balance = Token::GetBalance($this->from, $this->from_currency_name);
            }
        } else {
            // sell

            if ($this->to_type === 'coin') {
                $this->balance = Coin::GetBalance($this->from);
            }

            if ($this->to_type === 'token') {
                $this->balance = Token::GetBalance($this->from, $this->to_currency_name);
            }
        }

        $this->valid_token = true;

        if ($this->from_type === 'token') {
            if (empty(TokenList::GetInfo($this->from_currency_name))) {
                $this->valid_token = false;
            }
        }

        if ($this->to_type === 'token') {
            if (empty(TokenList::GetInfo($this->to_currency_name))) {
                $this->valid_token = false;
            }
        }

        $this->related_orders = \Saseul\Custom\Status\Exchange::GetOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name);
    }

    public function _MakeDecision()
    {
        if ($this->valid_token === false) {
            return Decision::REJECT;
        }

        if ($this->exchange_type === 'buy') {
            // buy

            if ((int) $this->from_amount > (int) $this->balance) {
                return Decision::REJECT;
            }
        } else {
            // sell

            if ((int) $this->to_amount > (int) $this->balance) {
                return Decision::REJECT;
            }
        }

        return Decision::ACCEPT;
    }

    public function _SetStatus()
    {
        $this->SetBalance();
        $orders_for_exchange = $this->GetOrdersForExchange();

        $remain_from_amount = $this->from_amount;
        $remain_to_amount = $this->to_amount;

        foreach ($orders_for_exchange as $k => $order) {
            // 내 양이 order 양보다 작으면
            if ((float) $order['to_amount'] > (float) $remain_to_amount) {
                // 기존 오더 수정
                $orders_for_exchange[$k]['to_amount'] = (float) $orders_for_exchange[$k]['to_amount'] - (float) $remain_to_amount;
                $orders_for_exchange[$k]['from_amount'] = (float) $orders_for_exchange[$k]['from_amount'] - (float) $remain_from_amount;
                \Saseul\Custom\Status\Exchange::SetOrder($order['from_type'], $order['from_currency_name'], $order['to_type'], $order['to_currency_name'], $order['eid'], $orders_for_exchange[$k]);

                // 내 처리 결과 등록
                $result = [
                    'from' => $this->from,
                    'from_type' => $this->from_type,
                    'from_currency_name' => $this->from_currency_name,
                    'from_amount' => $remain_from_amount,
                    'to' => $order['from'],
                    'to_type' => $this->to_type,
                    'to_currency_name' => $this->to_currency_name,
                    'to_amount' => $remain_to_amount,
                    'exchange_rate' => $this->exchange_rate,
                    'exchange_type' => $this->exchange_type,
                    'timestamp' => $this->timestamp,
                ];

                // remain 처리
                $remain_to_amount = 0;
                $remain_from_amount = 0;

                \Saseul\Custom\Status\Exchange::SetResult($result);
            } else {
                // 기존 오더 삭제
                \Saseul\Custom\Status\Exchange::SetDeleteOrder($order['eid']);

                // 내 처리 결과 등록
                $result = [
                    'from' => $this->from,
                    'from_type' => $this->from_type,
                    'from_currency_name' => $this->from_currency_name,
                    'from_amount' => $order['from_amount'],
                    'to' => $order['from'],
                    'to_type' => $this->to_type,
                    'to_currency_name' => $this->to_currency_name,
                    'to_amount' => $order['to_amount'],
                    'exchange_rate' => $order['exchange_rate'],
                    'exchange_type' => $this->exchange_type,
                    'timestamp' => $this->timestamp,
                ];

                // remain 처리 (sell)
                $remain_to_amount = (float) $remain_to_amount - (float) $order['to_amount'];
                $remain_from_amount = (float) $remain_to_amount * (float) $this->exchange_rate;

                \Saseul\Custom\Status\Exchange::SetResult($result);
            }

            if ((float) $remain_to_amount == 0) {
                break;
            }
        }

        // remain 있으면 새 오더 등록
        if ((float) $remain_to_amount > 0) {
            $order = [
                'from' => $this->from,
                'from_type' => $this->from_type,
                'from_currency_name' => $this->from_currency_name,
                'from_amount' => $remain_from_amount,
                'to_type' => $this->to_type,
                'to_currency_name' => $this->to_currency_name,
                'to_amount' => $remain_to_amount,
                'exchange_rate' => $this->exchange_rate,
                'exchange_type' => $this->exchange_type,
                'timestamp' => $this->timestamp,
            ];

            $ehash = hash('sha256', json_encode($order));
            $eid = $ehash . $this->timestamp;

            $order['eid'] = $eid;

            \Saseul\Custom\Status\Exchange::SetOrder($this->from_type, $this->from_currency_name, $this->to_type, $this->to_currency_name, $eid, $order);
        }
    }

    private function isValidAmount(): bool
    {
        $to_amount = (float) $this->to_amount;
        $exchange_rate = (float) $this->exchange_rate;
        $from_amount = (float) $this->from_amount;

        return $to_amount * $exchange_rate === $from_amount;
    }

    private function SetBalance()
    {
        if ($this->exchange_type === 'buy') {
            // buy

            $this->balance = (int) $this->balance - (int) $this->from_amount;

            if ($this->from_type === 'coin') {
                Coin::SetBalance($this->from, $this->balance);
            }

            if ($this->from_type === 'token') {
                Token::SetBalance($this->from, $this->from_currency_name, $this->balance);
            }
        } else {
            // sell

            $this->balance = (int) $this->balance - (int) $this->to_amount;

            if ($this->to_type === 'coin') {
                Coin::SetBalance($this->from, $this->balance);
            }

            if ($this->to_type === 'token') {
                Token::SetBalance($this->from, $this->to_currency_name, $this->balance);
            }
        }
    }

    private function GetOrdersForExchange()
    {
        $related_exchange_type = 'buy';

        if ($this->exchange_type === 'buy') {
            $related_exchange_type = 'sell';
        }

        $orders_for_exchange = [];
        $orders_exchange_rate = [];
        $orders_timestamp = [];

        foreach ($this->related_orders as $related_order) {
            if ($related_order['exchange_type'] !== $related_exchange_type) {
                continue;
            }

            if ($this->exchange_type === 'buy') {
                if ((float) $related_order['exchange_rate'] > (float) $this->exchange_rate) {
                    continue;
                }
            } else {
                if ((float) $related_order['exchange_rate'] < (float) $this->exchange_rate) {
                    continue;
                }
            }

            $orders_for_exchange[] = $related_order;
            $orders_exchange_rate[] = $related_order['exchange_rate'];
            $orders_timestamp[] = $related_order['timestamp'];
        }

        if ($this->exchange_type === 'buy') {
            array_multisort($orders_exchange_rate, $orders_timestamp, $orders_for_exchange);
        } else {
            array_multisort($orders_exchange_rate, SORT_DESC, $orders_timestamp, $orders_for_exchange);
        }

        return $orders_for_exchange;
    }
}
