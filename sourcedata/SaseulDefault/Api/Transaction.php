<?php

namespace Saseul\Api;

use Saseul\Common\ExternalApi;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\HttpStatus;
use Saseul\Util\RestCall;

// TODO: Transaction API Unit Test 추가 필요
class Transaction extends ExternalApi
{
    private $transactionResult;

    public function handle(): void
    {
        $this->initialize();
        if ($this->api->getValidity()) {
            $this->handleTransaction();
            $this->makeResult(HttpStatus::OK, $this->transactionResult);

            return;
        }

        $this->makeResult(HttpStatus::BAD_REQUEST);
    }

    private function initialize(): void
    {
        $thash = hash('sha256', json_encode($this->handlerData));
        $this->api->initialize(
            $this->handlerData,
            $thash,
            $this->public_key,
            $this->signature
        );
    }

    private function handleTransaction(): void
    {
        if (Tracker::isValidator(NodeInfo::getAddress())) {
            $this->AddTransaction();
            $this->transactionResult['message'] = 'Transaction is added';
        } else {
            $this->BroadcastTransaction();
            $this->transactionResult['message'] = 'Transaction is broadcast';
        }

        $this->transactionResult['transaction'] = $this->handlerData;
        $this->transactionResult['public_key'] = $this->public_key;
        $this->transactionResult['signature'] = $this->signature;
    }

    private function AddTransaction(): void
    {
        Chunk::saveApiChunk([
            'transaction' => $this->handlerData,
            'public_key' => $this->public_key,
            'signature' => $this->signature,
        ], $this->handlerData['timestamp']);
    }

    private function BroadcastTransaction(): void
    {
        $validator = Tracker::GetRandomValidator();
        if (isset($validator['host'])) {
            $host = $validator['host'];

            $url = "http://{$host}/transaction";
            $data = [
                'transaction' => json_encode($this->handlerData),
                'public_key' => $this->public_key,
                'signature' => $this->signature,
            ];

            $rest = RestCall::GetInstance();
            $rest->POST($url, $data);
        }
    }
}
