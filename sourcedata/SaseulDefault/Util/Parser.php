<?php

namespace Saseul\Util;

class Parser
{
    public static function objectToArray($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }

    public static function arrayToObject($d)
    {
        if (is_array($d)) {
            return (object) array_map('self::' . __FUNCTION__, $d);
        }

        return $d;
    }
}
