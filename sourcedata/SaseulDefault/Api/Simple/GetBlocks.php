<?php

namespace Saseul\Api\Simple;

use Saseul\Common\Api;
use Saseul\Core\Block;

class GetBlocks extends Api
{
    public function _process()
    {
        $this->data['committed'] = Block::GetLastBlocks();
    }
}
