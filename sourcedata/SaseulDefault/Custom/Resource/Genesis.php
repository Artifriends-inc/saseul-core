<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Core\Block;
use Saseul\Core\Chunk;
use Saseul\Core\Env;

/**
 * Class Genesis.
 *
 * Genesis chunk 를 만들기 위한 API 이다.
 *
 * @package Saseul\Custom\Resource
 */
class Genesis extends AbstractResource
{
    public function initialize(array $request, string $thash, string $publicKey, string $signature): void
    {
        parent::initialize($request, $thash, $publicKey, $signature);
    }

    public function getValidity(): bool
    {
        return parent::getValidity()
            && $this->genesisValidity();
    }

    public function process(): void
    {
        $genesisTransactionContent = [
            'transaction' => $this->request,
            'public_key' => $this->publicKey,
            'signature' => $this->signature,
        ];

        Chunk::saveApiChunk($genesisTransactionContent, $this->timestamp);
    }

    public function getResponse(): array
    {
        return ['status' => 'success'];
    }

    private function genesisValidity(): bool
    {
        return $this->from !== Env::$genesis['address']
            && Block::getCount() > 0;
    }
}
