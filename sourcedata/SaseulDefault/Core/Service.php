<?php

namespace Saseul\Core;

use Saseul\Constant\Role;
use Saseul\Daemon\Arbiter;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Supervisor;
use Saseul\Daemon\Validator;
use Saseul\System\Cache;
use Saseul\System\Database;

class Service
{
    public static function checkDatabase(): void
    {
        if (!Database::GetInstance()->IsConnect()) {
            self::end('db is not running; ');
        }
    }

    public static function checkCache(): void
    {
        if (!Cache::GetInstance()->IsConnect()) {
            self::end('cache is not running; ');
        }
    }

    public static function checkSaseulDaemon(): void
    {
        if (self::isDaemonRunning() === false) {
            self::end('saseul is not running; ');
        }
    }

    public static function isDaemonRunning()
    {
        if (!is_file('/var/saseul-origin/saseuld.pid') || !Property::isReady()) {
            return false;
        }

        return true;
    }

    public static function checkNodeInfo($opt = false): void
    {
        if (!NodeInfo::isExist()) {
            if ($opt === false) {
                self::end('There is no node.info ');
            }

            sleep(1);
            NodeInfo::resetNodeInfo();
        }
    }

    public static function initApi()
    {
        Env::load();

        self::checkDatabase();
        self::checkCache();
        self::checkSaseulDaemon();
        self::checkNodeInfo();
    }

    public static function initDaemon()
    {
        Env::load();

        self::checkDatabase();
        self::checkCache();

        Property::init();

        self::checkNodeInfo(true);

        Tracker::init();
        Generation::makeSourceArchive();
    }

    public static function initScript()
    {
        Env::load();

        self::checkDatabase();
        self::checkCache();
        self::checkNodeInfo();
    }

    public static function selectRole(): Node
    {
        switch (Tracker::GetRole(NodeInfo::getAddress())) {
            case Role::LIGHT:
                return Light::GetInstance();

                break;
            case Role::VALIDATOR:
                return Validator::GetInstance();

                break;
            case Role::SUPERVISOR:
                return Supervisor::GetInstance();

                break;
            case Role::ARBITER:
                return Arbiter::GetInstance();

                break;
        }

        self::end('invalid role; please check node info; ');

        return Light::GetInstance();
    }

    public static function end($msg)
    {
        echo PHP_EOL . $msg . PHP_EOL;
        exit();
    }
}
