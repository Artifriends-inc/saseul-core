<?php

// Root const
define('SASEUL_DIR', dirname(__DIR__));
define('ROOT_DIR', SASEUL_DIR . '/src');

require_once(SASEUL_DIR . '/vendor/autoload.php');

// init
session_start();
header('Access-Control-Allow-Origin: *');

use Saseul\Common\HandlerLoader;
use Saseul\Core\Service;
use Saseul\System\Terminator;
use Saseul\Util\Logger;

// Todo: SC-270 해당 내용은 다른 부분에서 구현되어 사용이 가능해야한다.
$service = new Service();
if (!$service->isInit()) {
    Terminator::exit(1);
}

// Todo: SC-269 API 분리 및 내부 프로토콜이 정해지면 뺀다.
if (!$service->isRunDaemon()) {
    Logger::getLogger(Logger::DAEMON)->err('SASEUL is not running.');
    Terminator::exit(1);
}

$handlerLoader = new HandlerLoader();
$response = $handlerLoader->run();
$handlerLoader->finish($response);
