<?php

namespace Saseul\Core;

use Saseul\Constant\Directory;
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
        if (!is_file(Directory::PID_FILE) || !Property::isReady()) {
            return false;
        }

        return true;
    }

    /**
     * API 클래스 생성시 초기값을 설정한다.
     *
     * @todo SC-143
     *
     * @return bool
     */
    public static function initApi(): bool
    {
        if (!self::isEnv()) {
            return false;
        }

        self::checkDatabase();
        self::checkCache();
        self::checkSaseulDaemon();

        return true;
    }

    /**
     * Daemon 클래스 생성시 초기값을 설정한다.
     *
     * @todo SC-144
     *
     * @return bool
     */
    public static function initDaemon(): bool
    {
        if (!self::isEnv()) {
            return false;
        }

        self::checkDatabase();
        self::checkCache();

        Property::init();
        Tracker::init();
        Generation::makeSourceArchive();

        return true;
    }

    /**
     * Script 클래스 생성시 초기값을 설정한다.
     *
     * @todo SC-145
     *
     * @return bool
     */
    public static function initScript(): bool
    {
        if (!self::isEnv()) {
            return false;
        }

        self::checkDatabase();
        self::checkCache();

        return true;
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

    /**
     * Env 환경을 불러오고 node 정보가 없으면 false.
     *
     * @return bool
     */
    private static function isEnv(): bool
    {
        Env::load();

        if (!NodeInfo::isExist()) {
            return false;
        }

        return true;
    }
}
