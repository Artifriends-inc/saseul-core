<?php

namespace Saseul\Util;

use Exception;
use Monolog;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Saseul\Core\Env;

/**
 * Logger provides functions for logging.
 */
class Logger
{
    public const MONGO = 'MongoDB';
    public const SCRIPT = 'Script';
    public const API = 'API';
    public const DAEMON = 'Daemon';

    /**
     * Monolog logger.
     *
     * @param string $appName
     *
     * @throws Exception
     *
     * @return Monolog\Logger
     *
     * @codeCoverageIgnore
     */
    public static function getLogger(string $appName): Monolog\Logger
    {
        $logger = new Monolog\Logger($appName);

        $fileHandler = new RotatingFileHandler(Env::$log['path'] . "/{$appName}.log", 30, Env::$log['level']);
        $fileHandler->setFormatter(new JsonFormatter());
        $fileHandler->pushProcessor(new IntrospectionProcessor());

        $streamHandler = new StreamHandler('php://stdout', Env::$log['level']);
        $streamHandler->setFormatter(new LineFormatter());
        $streamHandler->pushProcessor(new PsrLogMessageProcessor());

        $logger->pushHandler($fileHandler);
        $logger->pushHandler($streamHandler);

        return $logger;
    }

    /**
     * stream logger.
     *
     * @param string $appName
     *
     * @throws Exception
     *
     * @return Monolog\Logger
     * @codeCoverageIgnore
     */
    public static function getStreamLogger(string $appName): Monolog\Logger
    {
        $logger = new Monolog\Logger($appName);

        $streamHandler = new StreamHandler('php://stdout', Env::$log['level']);
        $streamHandler->setFormatter(new LineFormatter());
        $streamHandler->pushProcessor(new PsrLogMessageProcessor());

        $logger->pushHandler($streamHandler);

        return $logger;
    }
}
