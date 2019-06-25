<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Property;

class HashInfo extends Api
{
    private $round_key;

    function _init()
    {
        $this->round_key = $this->getParam($_REQUEST, 'round_key');
    }

    function _process()
    {
        $this->data = Property::hashInfo($this->round_key);
    }
}