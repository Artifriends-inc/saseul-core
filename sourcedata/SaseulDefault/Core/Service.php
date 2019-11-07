<?php

namespace Saseul\Core;

use Monolog;
use Saseul\Constant\Directory;
use Saseul\Constant\Role;
use Saseul\Daemon\Arbiter;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Supervisor;
use Saseul\Daemon\Validator;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\Util\Logger;

class Service
{
    /**
     * Database 연결을 확인한다.
     *
     * @param Monolog\Logger $logger
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function checkDatabase(Monolog\Logger $logger): bool
    {
        if (!Database::getInstance()->IsConnect()) {
            $logger->err('DB is not running.');

            return false;
        }

        return true;
    }

    /**
     * Cache 연결을 확인한다.
     *
     * @param Monolog\Logger $logger
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function checkCache(Monolog\Logger $logger): bool
    {
        if (!Cache::GetInstance()->IsConnect()) {
            $logger->err('Cache is not running.');

            return false;
        }

        return true;
    }

    /**
     * Env 설정을 확인한다.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function checkEnv(): bool
    {
        Env::load();

        if (!self::isEnv()) {
            echo 'Env is not settings.';

            return false;
        }

        return true;
    }

    /**
     * Daemon 이 실행중인지 확인한다.
     *
     * @return bool
     */
    public static function isDaemonRunning(): bool
    {
        return !(!is_file(Directory::PID_FILE) || !Property::isReady());
    }

    /**
     * API 클래스 생성시 초기값을 설정한다.
     *
     * @throws \Exception
     *
     * @return bool
     *
     * @todo SC-143
     */
    public static function initApi(): bool
    {
        if (!self::checkEnv()) {
            return false;
        }

        $logger = Logger::getLogger(Logger::API);

        if (!self::checkDatabase($logger)) {
            return false;
        }

        if (!self::checkCache($logger)) {
            return false;
        }

        // Todo: API 분리시 해당 부분은 제외될 수 있다.
        if (!self::isDaemonRunning()) {
            $logger->err('SASEUL is not running');

            return false;
        }

        return true;
    }

    /**
     * Daemon 클래스 생성시 초기값을 설정한다.
     *
     * @throws \Exception
     *
     * @return bool
     *
     * @todo SC-144
     */
    public static function initDaemon(): bool
    {
        if (!self::checkEnv()) {
            return false;
        }

        $logger = Logger::getLogger(Logger::DAEMON);

        if (!self::checkDatabase($logger)) {
            return false;
        }

        if (!self::checkCache($logger)) {
            return false;
        }

        Property::init();
        Tracker::init();
        Generation::archiveSource();

        return true;
    }

    /**
     * Script 클래스 생성시 초기값을 설정한다.
     *
     * @throws \Exception
     *
     * @return bool
     *
     * @todo SC-145
     */
    public static function initScript(): bool
    {
        if (!self::checkEnv()) {
            return false;
        }

        $logger = Logger::getLogger(Logger::SCRIPT);

        if (!self::checkDatabase($logger)) {
            return false;
        }

        if (!self::checkCache($logger)) {
            return false;
        }

        return true;
    }

    public static function selectRole(): ?Node
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
            default:
                return null;

                break;
        }
    }

    /**
     * Env 환경을 불러오고 node 정보가 없으면 false.
     *
     * @return bool
     */
    private static function isEnv(): bool
    {
        return NodeInfo::isExist();
    }
}
