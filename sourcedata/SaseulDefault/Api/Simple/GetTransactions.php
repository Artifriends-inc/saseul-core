<?php

namespace Saseul\Api\Simple;

use Saseul\Common\Api;
use Saseul\Core\Block;

class GetTransactions extends Api
{
    public function _process()
    {
        $this->data = Block::GetLastTransactions(50);
    }
}
