<?php

namespace Saseul\Common;

use Exception;
use Saseul\Constant\Directory;
use Saseul\Constant\Role;
use Saseul\Core\NodeInfo;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\Daemon\Arbiter;
use Saseul\Daemon\Light;
use Saseul\Daemon\Node;
use Saseul\Daemon\Supervisor;
use Saseul\Daemon\Validator;
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

        $node = $this->getNodeInstance();

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

    /**
     * 정해진 Role 에 대한 Instance를 반환한다.
     *
     * @throws Exception
     *
     * @return null|Node
     */
    private function getNodeInstance(): ?Node
    {
        $role = Tracker::getRole(NodeInfo::getAddress());

        if ($role === Role::LIGHT) {
            return Light::GetInstance();
        }

        if ($role === Role::VALIDATOR) {
            return Validator::GetInstance();
        }

        if ($role === Role::ARBITER) {
            return Arbiter::GetInstance();
        }

        if ($role === Role::SUPERVISOR) {
            return Supervisor::GetInstance();
        }

        return null;
    }
}
