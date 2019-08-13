<?php

namespace Saseul;

class Version
{
    const CURRENT = '1.0.0.3';
    const LENGTH_LIMIT = 64;

    public static function isValid($version)
    {
         // TODO: 현재 버전에 문자가 섞여 있어도 유효한 값으로 인식, 의도한 바인지 검토
        return is_string($version) && !empty($version) && (mb_strlen($version) < self::LENGTH_LIMIT);
    }
}
