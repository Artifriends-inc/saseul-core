<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Property;

class Stat extends Script
{
    public function _process()
    {
        if (isset($this->arg[0]) && $this->arg[0] === '-f') {
            while (true) {
                static::log()->info('Property value', [Property::getAll()]);
                static::log()->info('Exit : Ctrl + C ');
                sleep(1);
            }
        } else {
            static::log()->info('Property value', [Property::getAll()]);
        }
    }
}
