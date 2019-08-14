<?php

namespace Saseul\Custom\Request;

use Saseul\Common\AbstractRequest;
use Saseul\Custom\Method\Attributes;

class GetRole extends AbstractRequest
{
    public function getResponse(): array
    {
        $from = $this->request['from'];

        return Attributes::GetRole($from);
    }
}
