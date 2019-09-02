<?php

namespace Saseul\Api;

use ReflectionClass;
use Saseul\Common\Api;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\Util\RestCall;

class Transaction extends Api
{
    protected $rest;
    private $transaction;
    private $public_key;
    private $signature;
    private $apiName;
    private $api;

    // TODO: RestCall을 바로 사용하지 않으므로 사용될 때 Instance를 가져오도록 변경
    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _init()
    {
        $this->transaction = json_decode($this->getParam($_REQUEST, 'transaction', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
    }

    public function _process()
    {
        $this->existApi();
        $this->createApiInstance();
        $this->initialize();
        $this->validate();
    }

    public function _end()
    {
        if (Tracker::IsValidator(NodeInfo::getAddress())) {
            $this->AddTransaction();
            $this->data['result'] = 'Transaction is added';
        } else {
            $this->BroadcastTransaction();
            $this->data['result'] = 'Transaction is broadcast';
        }

        $this->data['transaction'] = $this->transaction;
        $this->data['public_key'] = $this->public_key;
        $this->data['signature'] = $this->signature;
    }

    public function AddTransaction()
    {
        Chunk::saveApiChunk([
            'transaction' => $this->transaction,
            'public_key' => $this->public_key,
            'signature' => $this->signature,
        ], $this->transaction['timestamp']);
    }

    public function BroadcastTransaction()
    {
        $validator = Tracker::GetRandomValidator();
        if (isset($validator['host'])) {
            $host = $validator['host'];

            $url = "http://{$host}/transaction";
            $data = [
                'transaction' => json_encode($this->transaction),
                'public_key' => $this->public_key,
                'signature' => $this->signature,
            ];

            $this->rest->POST($url, $data);
        }
    }

    private function existApi(): void
    {
        $type = $this->getParam($this->transaction, 'type');
        $this->apiName = 'Saseul\\Custom\\Transaction\\' . $type;
        if (class_exists($this->apiName) === false) {
            $this->error('Invalid Transaction');
        }
    }

    private function createApiInstance(): void
    {
        $this->api = (
            new ReflectionClass($this->apiName)
        )->newInstance();
    }

    private function initialize(): void
    {
        $thash = hash('sha256', json_encode($this->transaction));
        $this->api->initialize(
            $this->transaction,
            $thash,
            $this->public_key,
            $this->signature
        );
    }

    private function validate(): void
    {
        if ($this->api->getValidity() === false) {
            $this->error('Invalid Transaction');
        }
    }
}
