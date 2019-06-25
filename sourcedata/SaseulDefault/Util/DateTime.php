<?php

namespace Saseul\Util;

class DateTime
{
    /**
     * This returns the current Unix timestamp with microseconds.
     *
     * @return int indicates the current time
     */
    public static function Microtime()
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000000);
    }

    /**
     * This returns comma formatted current Unix timestamp with microseconds.
     *
     * example: When the microseconds is 1548727355484738,
     * this returns 1548727355.4848 (double)
     *
     * @return float indicates the current time
     */
    public static function MicrotimeWithComma()
    {
        // FIXME: Where is comma insertion?
        return array_sum(explode(' ', microtime()));
    }

    /**
     * This returns the current Unix timestamp with milliseconds.
     *
     * @return int indicates the current time
     */
    public static function Millitime()
    {
        return (int) (array_sum(explode(' ', microtime())) * 1000);
    }

    /**
     * This returns formatted date string based on system clock.
     *
     * example: On January 28, 2019 at 15:37:21, will return 20190128153721.
     *
     * @return string current datetime
     */
    public static function Date()
    {
        return date('YmdHis');
    }

    public static function toTime(int $microtime)
    {
        return (int)($microtime / 1000000);
    }
}
