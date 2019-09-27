<?php

namespace Saseul\System;

use Saseul\Util\Mongo;

/**
 * Database provides DB initialization function and a getter function for the
 * singleton Database instance.
 *
 * @todo Singleton 형태로 되어있다고 하는데 단순 getInstance 로는 힘들다.
 *       추가 작업이 필요함.
 *       https://designpatternsphp.readthedocs.io/en/latest/Creational/Singleton/README.html
 */
final class Database extends Mongo
{
    protected static $instance;

    /**
     * Return the singleton Database Instance.
     *
     * @throws \Exception
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
