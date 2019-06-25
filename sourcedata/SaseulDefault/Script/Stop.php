<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Property;

class Stop extends Script
{
    public function _process()
    {
        Property::isReady(false);
    }
}
