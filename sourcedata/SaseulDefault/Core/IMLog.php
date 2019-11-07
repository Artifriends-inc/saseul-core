<?php

// Internal message log

namespace Saseul\Core;

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

    /**
     * @return string
     *
     * @deprecated 사용하는 곳이 없다.
     *
     * @todo SC-230
     */
    public static function get()
    {
        $iMLogProperty = Property::iMLog();

        if (empty($iMLogProperty)) {
            return '';
        }

        return self::parse($iMLogProperty);
    }

    /**
     * @param array $iMLogProperty
     *
     * @return string
     *
     * @deprecated 사용하는 곳이 바로 위다.
     *
     * @todo SC-230
     */
    public static function parse(array $iMLogProperty)
    {
        $str = '';

        foreach ($iMLogProperty as $row) {
            if (!is_string($row)) {
                continue;
            }

            $str .= $row . PHP_EOL;
        }

        return $str;
    }
}
