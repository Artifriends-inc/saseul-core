<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Core\Property;
use Saseul\Util\Logger;

class Stat extends Script
{
    public function _process()
    {
        if (isset($this->arg[0]) && $this->arg[0] === '-f') {
            while (true) {
                Logger::Log(Property::getAll());
                Logger::EchoLog('Exit : Ctrl + C ' . PHP_EOL);
                sleep(1);
            }
        } else {
            Logger::Log(Property::getAll());
        }
    }
}
