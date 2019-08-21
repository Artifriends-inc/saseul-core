<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Core\Chunk;
use Saseul\Core\Env;

/**
 * Class Deposit.
 *
 * Genesis 이후 실행되는 API 이다.
 */
class Deposit extends AbstractResource
{
    public function initialize(array $request, string $thash, string $publicKey, string $signature): void
    {
        parent::initialize($request, $thash, $publicKey, $signature);
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->depositValidity();
    }

    public function process(): void
    {
        $depositTransactionContent = [
            'transaction' => $this->request,
            'public_key' => $this->publicKey,
            'signature' => $this->signature,
        ];

        Chunk::saveApiChunk($depositTransactionContent, $this->timestamp);
    }

    public function getResponse(): array
    {
        return ['status' => 'success'];
    }

    private function depositValidity(): bool
    {
        return $this->from !== Env::$genesis['address'];
    }
}
