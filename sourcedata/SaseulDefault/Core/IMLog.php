<?php

namespace Saseul\Core;

/**
 * Class IMLog.
 *
 * Internal message log
 */
class IMLog
{
    public static function add($log)
    {
        $iMLogProperty = Property::iMLog();

        if (!is_array($iMLogProperty)) {
            $iMLogProperty = [];
        }

        $iMLogProperty[] = $log;

        if (count($iMLogProperty) > 100) {
            unset($iMLogProperty[0]);
            $iMLogProperty = array_values($iMLogProperty);
        }

        Property::iMLog($iMLogProperty);
    }

    public static function reset()
    {
        Property::iMLog([]);
    }
}
