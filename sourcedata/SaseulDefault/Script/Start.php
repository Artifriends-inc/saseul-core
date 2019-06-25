<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Property;

class Start extends Script
{
    public function _process()
    {
        Property::isReady(true);
    }
}
