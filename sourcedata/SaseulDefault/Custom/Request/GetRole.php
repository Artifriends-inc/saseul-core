<?php

namespace Saseul\Custom\Request;

use MongoDB\Driver\Exception\Exception;
use Saseul\Custom\Method\Attributes;

/**
 * Class GetRole.
 * 요청한 Account Role 정보를 가져온다.
 */
class GetRole extends AbstractRequest
{
    /**
     * @throws Exception
     *
     * @return array
     */
    public function getResponse(): array
    {
        $from = $this->from;

        return Attributes::GetRole($from);
    }
}
