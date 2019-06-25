<?php

namespace Saseul\Custom\Transaction;

use Saseul\Custom\Status\AuthToken;
use Saseul\Constant\Decision;
use Saseul\System\Key;
use Saseul\Common\Transaction;
use Saseul\Version;

class UseAuthToken extends Transaction
{
    public const TYPE = 'UseAuthToken';

    protected $transaction;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $status_key;

    private $type;
    private $version;
    private $from;
    private $token_code;
    private $token_authkey;
    private $transactional_data;
    private $timestamp;

    private $tid;
    private $auth_code;
    private $token;

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
        if (isset($this->transaction['token_authkey'])) {
            $this->token_authkey = $this->transaction['token_authkey'];
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
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function _LoadStatus()
    {
        $this->auth_code = Key::makePublicKey($this->token_authkey);
        $this->tid = $this->token_code . "_" . $this->auth_code;
        AuthToken::LoadToken($this->tid);
    }

    public function _GetStatus()
    {
        $this->token = AuthToken::GetValue($this->tid);
    }

    public function _MakeDecision()
    {
        if ($this->token !== [] && isset($this->token['status']) && $this->token['status'] === 'unused') {
            return Decision::ACCEPT;
        }

        return Decision::REJECT;
    }

    public function _SetStatus()
    {
        $value = [
            'owner' => $this->from,
            'code' => $this->token_code,
            'auth_code' => $this->auth_code,
            'status' => 'used',
        ];

        AuthToken::SetValue($this->tid, $value);
    }
}
