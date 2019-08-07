<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Tracker;

class Nodes extends Api
{
    public function _process()
    {
        $this->data = Tracker::getAccessibleNodes();
    }
}
