<?php

namespace Saseul\Core;

use Saseul\System\Cache;

class Property
{
    public static function isReady($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function isRoundRunning($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function round($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function roundInfo($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function hashInfo($roundKey, $value = null)
    {
        return self::gs(__FUNCTION__ . $roundKey, $value);
    }

    // for check;
    public static function aliveNode($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function excludedHost($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function subjectNode($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function registerRequest($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function sourceHash($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function sourceVersion($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function banList($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function iMLog($value = null)
    {
        return self::gs(__FUNCTION__, $value);
    }

    public static function init()
    {
        self::isReady(true);
        self::isRoundRunning(false);
        self::iMLog([]);

        self::aliveNode([]);
        self::excludedHost([]);
        self::subjectNode([]);
        self::registerRequest([]);

        self::sourceHash('');
        self::sourceVersion('');
    }

    public static function getAll()
    {
        $properties = self::getProperties();
        $all = [];

        foreach ($properties as $property) {
            $all[$property] = self::$property();
        }

        return $all;
    }

    public static function getProperties()
    {
        $all = get_class_methods(self::class);
        $properties = [];
        $excludes = ['init', 'getAll', 'gs', 'getCache', 'setCache', 'getProperties', 'hashInfo'];

        foreach ($all as $item) {
            if (!in_array($item, $excludes)) {
                $properties[] = $item;
            }
        }

        return $properties;
    }

    /**
     * Cache 에서 값을 가져온다.
     *
     * @param      $name
     * @param null $value
     *
     * @return null|mixed
     */
    private static function gs($name, $value = null)
    {
        if ($value === null) {
            return self::getCache($name);
        }
        self::setCache($name, $value);

        return null;
    }

    private static function getCache($name)
    {
        return Cache::GetInstance()->get("p_{$name}");
    }

    private static function setCache($name, $value)
    {
        Cache::GetInstance()->set("p_{$name}", $value);
    }
}
