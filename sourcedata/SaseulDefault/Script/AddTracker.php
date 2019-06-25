<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Tracker;

class AddTracker extends Script
{
    public function _process()
    {
        $address = $this->ask('address? ');
        $host = $this->ask('host? ');

        Tracker::setHosts([[
            'address' => $address,
            'host' => $host
        ]]);
    }
}
