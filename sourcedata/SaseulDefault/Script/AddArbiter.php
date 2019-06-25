<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Tracker;

class AddArbiter extends Script
{
    public function _process()
    {
        $address = $this->ask('address? ');

        Tracker::SetArbiter($address);
    }
}
