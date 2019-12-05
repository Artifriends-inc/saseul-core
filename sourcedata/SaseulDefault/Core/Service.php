<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\Directory;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\Util\Logger;

class Service
{
    /**
     * Daemon 이 실행중인지 확인한다.
     *
     * @return bool
     */
    public function isRunDaemon(): bool
    {
        return is_file(Directory::PID_FILE) && Property::isReady();
    }

    /**
     * 각 loader를 실행하기전 초기값을 체크한다.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isInit(): bool
    {
        Env::load();

        return static::isSetEnv() && static::isConnectDatabase() && static::isConnectCache();
    }

    /**
     * Database 연결을 확인한다.
     *
     * @throws Exception
     *
     * @return bool
     */
    private static function isConnectDatabase(): bool
    {
        if (Database::getInstance()->IsConnect()) {
            return true;
        }

        Logger::getLogger(Logger::DAEMON)->err('DB is not running.');

        return false;
    }

    /**
     * Cache 연결을 확인한다.
     *
     * @throws Exception
     *
     * @return bool
     */
    private static function isConnectCache(): bool
    {
        if (Cache::GetInstance()->isConnect()) {
            return true;
        }

        Logger::getLogger(Logger::DAEMON)->err('Cache is not running.');

        return false;
    }

    /**
     * Env 설정을 확인한다.
     *
     * @throws Exception
     *
     * @return bool
     */
    private static function isSetEnv(): bool
    {
        if (NodeInfo::isExist()) {
            return true;
        }

        echo 'Env is not settings.';

        return false;
    }
}
