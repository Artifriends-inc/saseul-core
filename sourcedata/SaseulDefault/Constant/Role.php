<?php

namespace Saseul\Constant;

class Role
{
    const LIGHT = 'light';
    const VALIDATOR = 'validator';
    const SUPERVISOR = 'supervisor';
    const ARBITER = 'arbiter';
    const ROLES = [self::VALIDATOR, self::SUPERVISOR, self::ARBITER, self::LIGHT];
    const FULL_NODES = [self::VALIDATOR, self::SUPERVISOR, self::ARBITER];

    public static function isExist($role)
    {
        return in_array($role, self::ROLES);
    }
}
