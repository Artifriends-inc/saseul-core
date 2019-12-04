<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Tracker;

class Ban extends Script
{
    public function _process()
    {
        $host = $this->ask('host? ');

        Tracker::setBanHost($host);
    }
}
