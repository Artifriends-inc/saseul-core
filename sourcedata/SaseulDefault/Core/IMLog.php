<?php

namespace Saseul\Core;

/**
 * Class IMLog.
 *
 * Internal message log
 */
class IMLog
{
    public static function reset()
    {
        Property::iMLog([]);
    }
}
