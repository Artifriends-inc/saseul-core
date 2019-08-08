<?php

namespace Saseul\Util;

use Monolog;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Logger provides functions for logging.
 */
class Logger
{
    /**
     * Prints information of an object and calling location.
     *
     * Optionally exit the program completely.
     *
     * @param mixed $obj    Object that need to print for inspect its value.
     * @param bool  $option When true, exit the program.
     */
    public static function Log($obj, $option = false)
    {
        print_r('[Log]' . xdebug_call_file() . ':' . xdebug_call_line() . PHP_EOL);
        print_r($obj);
        print_r(PHP_EOL);
        if ($option) {
            exit();
        }
    }

    /**
     * Prints information of an object.
     *
     * @param mixed $obj Object that need to print for inspect its value.
     */
    public static function EchoLog($obj)
    {
        print_r($obj);
        print_r(PHP_EOL);
    }

    /**
     * Prints an error message with an object and calling location.
     * And Exit the program completely.
     *
     * @param array $obj Object that need to print for inspect its value.
     */
    public static function Error($obj = null)
    {
        print_r('[Error]' . xdebug_call_file() . ':' . xdebug_call_line() . PHP_EOL);

        if ($obj !== null) {
            print_r($obj);
            print_r(PHP_EOL);
        }

        exit();
    }

    /**
     * Monolog logger.
     *
     * @param string $appName
     *
     * @throws \Exception
     *
     * @return Monolog\Logger
     *
     * @codeCoverageIgnore
     */
    public static function getLogger(string $appName): Monolog\Logger
    {
        $logger = new Monolog\Logger($appName);

        $fileHandler = new RotatingFileHandler(SASEUL_DIR . "/data/logs/{$appName}.log", 30, Monolog\Logger::DEBUG);
        $fileHandler->setFormatter(new JsonFormatter());
        $fileHandler->pushProcessor(new IntrospectionProcessor());

        $streamHandler = new StreamHandler('php://stdout', Monolog\Logger::DEBUG);
        $streamHandler->setFormatter(new LineFormatter());
        $streamHandler->pushProcessor(new PsrLogMessageProcessor());

        $logger->pushHandler($fileHandler);
        $logger->pushHandler($streamHandler);

        return $logger;
    }
}
