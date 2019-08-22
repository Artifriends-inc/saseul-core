<?php

namespace Saseul\System;

use Saseul\Util\MongoDb;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 */
class Database extends MongoDb
{
    protected static $instance = null;

    /**
     * Return the singleton Database Instance.
     *
     * @return Database The singleton Databse instance.
     */
    public static function GetInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
