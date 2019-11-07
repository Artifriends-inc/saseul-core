<?php

// Root const
define('SASEUL_DIR', dirname(__DIR__));
define('ROOT_DIR', SASEUL_DIR . '/src');

require_once(SASEUL_DIR . '/vendor/autoload.php');

// init
session_start();
header('Access-Control-Allow-Origin: *');

use Saseul\Common\HandlerLoader;

$handlerLoader = new HandlerLoader();
$handlerLoader->run();
