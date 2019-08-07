<?php

namespace Saseul\System;

use Saseul\Util\RestCall;

class IPChecker
{
    public static function getPublicIP()
    {
        $rest = RestCall::GetInstance();
        $url = 'https://ip4.seeip.org';
        $ssl = true;

        $ip = $rest->GET($url, $ssl);

        return trim($ip);
    }
}
