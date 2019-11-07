<?php

namespace Saseul\Common;

use Saseul\Constant\Directory;
use Saseul\Core\Property;
use Saseul\Core\Service;
use Saseul\Core\Tracker;
use Saseul\Util\Logger;

class DaemonLoader
{
    private $logger;

    public function __construct()
    {
        $pidPath = Directory::PID_FILE;

        posix_setgid(getmygid());
        posix_setuid(getmyuid());

        Daemon::setOption($pidPath, posix_getgid(), posix_getuid());
        Daemon::start();

        $this->logger = Logger::getLogger(Logger::DAEMON);

        if (!Service::initDaemon()) {
            Daemon::stop();
        }
    }

    public function main(): void
    {
        while (Daemon::$isDying === false) {
            $this->round();

            Daemon::iterate(10000);
        }

        Daemon::stop();
    }

    public function round(): void
    {
        if (Property::isReady() === false) {
            return;
        }

        Property::isRoundRunning(true);

        Property::banList(Tracker::banList());

        $node = Service::selectRole();

        if ($node === null) {
            $this->logger->err('Invalid role. Please check node info');
            $this->stop();
        }

        $node->round();

        Property::isRoundRunning(false);
    }

    public function stop()
    {
        Daemon::$isDying = true;
        Daemon::info(PHP_EOL . 'end');
    }
}
