<?php

namespace Saseul\Constant;

class Role
{
    public const LIGHT = 'light';
    public const VALIDATOR = 'validator';
    public const SUPERVISOR = 'supervisor';
    public const ARBITER = 'arbiter';
    public const ROLES = [self::VALIDATOR, self::SUPERVISOR, self::ARBITER, self::LIGHT];
    public const FULL_NODES = [self::VALIDATOR, self::SUPERVISOR, self::ARBITER];

    public static function isExist($role)
    {
        return in_array($role, self::ROLES);
    }
}
