<?php
# TODO: shell_exec 없애야 함.
namespace Saseul\Common;

class Daemon
{
    public static $pidPath;
    public static $pid;
    public static $gid;
    public static $uid;

    public static $isDying = false;

    public static function setOption(string $pidPath, int $gid, int $uid) {
        self::$pidPath = $pidPath;
        self::$gid = $gid;
        self::$uid = $uid;
    }

    public static function start(): void
    {
        self::checkParam();
        self::fork();
        self::setEnv();
    }
    
    public static function stop(): void
    {
        if (self::$isDying === true) {
            return;
        }

        self::$isDying = true;

        if (!is_numeric(self::$pid)) {
            return;
        }

//        unlink(self::$pidPath);
//        shell_exec('kill -9 ' . self::$pid);

        shell_exec('service saseuld stop');
        exit();
    }

    public static function restart(): void
    {
        if (self::$isDying === true) {
            return;
        }

        self::$isDying = true;

        if (!is_numeric(self::$pid)) {
            return;
        }

        shell_exec('service saseuld restart');
        exit();
    }
    
    public static function iterate(int $microSeconds): void
    {
        usleep($microSeconds);
        clearstatcache();
        
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public static function checkParam(): void
    {
        if (self::$pidPath === null) {
            self::error('error');
        }

        if (self::$gid === null) {
            self::error('error');
        }

        if (self::$uid === null) {
            self::error('error');
        }

        if ((php_sapi_name() !== 'cli')) {
            self::error('error');
        }

        if (!function_exists('posix_getpid')) {
            self::error('Need posix extension');
        }

        if (self::isRunning()) {
            self::error('Daemon is already running. ');
        }

        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }
    
    public static function fork(): void
    {
        switch ($pid = pcntl_fork()) {
            case 0:
                self::$pid = posix_getpid();
                @umask(0);
                break;
            case -1:
                self::error('Process cannot be forked. ');
                break;
            default:
                exit();
                break;
        }
    }
    
    public static function setEnv(): void
    {
        if (!file_put_contents(self::$pidPath, self::$pid)) {
            self::error('Unable to write pidfile');
        }

        if (!chmod(self::$pidPath, 0644)) {
            self::error('Unable to chmod pidfile');
        }

        if (!chgrp(self::$pidPath, self::$gid)) {
            self::error('Unable to change group');
        }

        if (!chown(self::$pidPath, self::$uid)) {
            self::error('Unable to change user');
        }

        if (!posix_setgid(self::$gid)) {
            self::error('Unable to change group');
        }

        if (!posix_setuid(self::$uid)) {
            self::error('Unable to change group');
        }

        declare(ticks = 1);
    }

    public static function isRunning(): bool
    {
        if (!is_file(self::$pidPath)) {
            return false;
        }

        $pid = self::readPid();

        if ($pid === '') {
            return false;
        }

        if (!posix_kill(intval($pid), 0)) {
            unlink(self::$pidPath);
        }

        return true;
    }

    public static function readPid(): string
    {
        $f = fopen(self::$pidPath, 'r');
        if (!$f) {
            return '';
        }
        $pid = fread($f, filesize(self::$pidPath));
        fclose($f);

        return $pid;
    }
    
    public static function info(string $msg): void
    {
        print_r($msg . PHP_EOL);
    }

    public static function error(string $msg): void
    {
        self::info($msg);
        exit();
    }
}