<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Core\Chunk;

class ChangeRole extends AbstractResource
{
    public function process(): void
    {
        $changeRoleContent = [
            'transaction' => $this->request,
            'public_key' => $this->publicKey,
            'signature' => $this->signature,
        ];

        Chunk::saveApiChunk($changeRoleContent, $this->timestamp);
    }

    /**
     * @codeCoverageIgnore 반환값만 있기에 테스트할 필요없다.
     *
     * @return array
     */
    public function getResponse(): array
    {
        return ['status' => 'success'];
    }
}
