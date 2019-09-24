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

    /**
     * 프로세스를 종료한다.
     *
     * @todo 사용할거면 log가 남도록 해야된다.
     *
     * @param $status
     *
     * @throws \Exception
     */
    public static function exit($status)
    {
        if (self::$testMode) {
            self::setLiveMode();

            throw new \Exception($status);
        }
        exit();
    }
}
