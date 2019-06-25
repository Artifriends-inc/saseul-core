<?php

namespace Saseul\Custom\Transaction;

use Saseul\Custom\Status\AuthToken;
use Saseul\Custom\Status\AuthTokenInfo;
use Saseul\Constant\Decision;
use Saseul\System\Key;
use Saseul\Common\Transaction;
use Saseul\Version;

class CreateAuthToken extends Transaction
{
    public const TYPE = 'CreateAuthToken';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $token_code;
    private $token_info;
    private $token_list;
    private $transactional_data;
    private $timestamp;

    private $publish_token_info;

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
        if (isset($this->transaction['token_code'])) {
            $this->token_code = $this->transaction['token_code'];
        }
        if (isset($this->transaction['token_info'])) {
            $this->token_info = $this->transaction['token_info'];
        }
        if (isset($this->transaction['token_list'])) {
            $this->token_list = $this->transaction['token_list'];
        }
        if (isset($this->transaction['transactional_data'])) {
            $this->transactional_data = $this->transaction['transactional_data'];
        }
        if (isset($this->transaction['timestamp'])) {
            $this->timestamp = $this->transaction['timestamp'];
        }
    }

    public function _GetValidity(): bool
    {
        return Version::isValid($this->version)
            && is_numeric($this->timestamp)
            && is_string($this->token_code)
            && (mb_strlen($this->token_code) <= 64)
            && is_array($this->token_list)
            && (count($this->token_list) > 0)
            && (count($this->token_list) <= 1000)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        AuthTokenInfo::LoadInfo($this->token_code);
    }

    public function _GetStatus()
    {
        $this->publish_token_info = AuthTokenInfo::GetInfo($this->token_code);
    }

    public function _MakeDecision()
    {
        foreach ($this->token_info as $token_info_item) {
            if (isset($token_info_item['name']) && isset($token_info_item['value'])) {
                continue;
            }

            return Decision::REJECT;
        }

        if ($this->publish_token_info == []) {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _SetStatus()
    {
        AuthTokenInfo::SetInfo($this->token_code, $this->token_info);

        foreach ($this->token_list as $v) {
            $tid = $this->token_code . "_" . $v;
            $value = [
                'owner' => $this->from,
                'code' => $this->token_code,
                'auth_code' => $v,
                'status' => 'unused',
            ];
            AuthToken::SetValue($tid, $value);
        }
    }
}
