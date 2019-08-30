<?php

namespace Saseul\System;

class Terminator
{
    private static $testMode = false;

    public static function setTestMode()
    {
        self::$testMode = true;
    }

    public static function setLiveMode()
    {
        self::$testMode = false;
    }

    public static function exit($status)
    {
        if (self::$testMode) {
            self::setLiveMode();

            throw new \Exception($status);
        }
        exit();
    }
}
