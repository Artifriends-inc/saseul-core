<?php

namespace Saseul\Custom\Request;

use Saseul\Common\RequestInterface;
use Saseul\Custom\Method\Attributes;
use Saseul\Custom\Method\Coin;
use Saseul\Custom\Method\Token;
use Saseul\System\Key;
use Saseul\Version;

class GetAccountInfo implements RequestInterface
{
    public const TYPE = 'GetAccountInfo';

    protected $request;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $transactional_data;
    private $timestamp;

    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
        $this->request = $request;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->request['type'] ?? '';
        $this->version = $this->request['version'] ?? '';
        $this->from = $this->request['from'] ?? '';
        $this->transactional_data = $this->request['transactional_data'] ?? '';
        $this->timestamp = $this->request['timestamp'] ?? 0;
    }

    public function getValidity(): bool
    {
        return Version::isValid($this->version)
            && !empty($this->timestamp)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function getResponse(): array
    {
        $from = $this->request['from'];
        $all = Coin::GetAll([$from]);
        $balance = $all[$from]['balance'];
        $deposit = $all[$from]['deposit'];
        $token = Token::GetAll([$from]);
        $token = $token[$from];

        return [
            'coin' => [
                'balance' => $balance,
                'deposit' => $deposit,
            ],
            'role' => Attributes::GetRole($from),
            'token' => $token,
        ];
    }
}
