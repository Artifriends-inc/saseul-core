<?php

namespace Saseul\Util;

/**
 * Class TypeChecker provides a function to check the data type.
 */
class TypeChecker
{
    /**
     * Check which data structure is correct.
     *
     * @param array $tpl   The data to be used as a schema.
     * @param array $value The data to be checked.
     *
     * @return bool True if the $value is correct.
     *
     * @todo 해당 부분을 클래스로 확인할 수 있도록 변경해준다.
     */
    public static function StructureCheck($tpl, $value)
    {
        foreach ($tpl as $k => $v) {
            if (!isset($value[$k])) {
                return false;
            }
            if ($v !== null && gettype($v) !== gettype($value[$k])) {
                return false;
            }
            if (is_array($v) && count($v) > 0 && self::StructureCheck($v, $value[$k]) === false) {
                return false;
            }
        }

        return true;
    }

    public static function findMostItem(array $array, string $key)
    {
        $cnt = [];

        foreach ($array as $item) {
            if (!isset($cnt[$item[$key]])) {
                $cnt[$item[$key]] = 1;
            } else {
                $cnt[$item[$key]] = $cnt[$item[$key]] + 1;
            }
        }

        if (count($cnt) > 1) {
            $k = array_search(max(array_values($cnt)), $cnt);

            foreach ($array as $item) {
                if ($item[$key] === $k) {
                    return [
                        'unique' => false,
                        'item' => $item,
                    ];
                }
            }
        }

        return [
            'unique' => true,
            'item' => $array[0],
        ];
    }
}
