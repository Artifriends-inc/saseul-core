<?php

use PHPUnit\Framework\TestCase;
use Saseul\Core\Service;

class ServiceTest extends TestCase
{
    private $pidPath;

    protected function setUp(): void
    {
        $this->pidPath = \Saseul\Constant\Directory::PID_FILE;
    }

    protected function tearDown(): void
    {
        putenv('NODE_HOST=web');
        putenv('NODE_ADDRESS=0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85');
        putenv('NODE_PUBLIC_KEY=52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3');
        putenv('NODE_PRIVATE_KEY=a745fbb3860f243293a66a5fcadf70efc1fa5fa5f0254b3100057e753ef0d9bb');
    }

    public function testGivenEmptyEnvThenInitApiRaisesException(): void
    {
        // Arrange
        $this->prepareEnv();

        // Act
        $act = Service::initApi();

        // Assert
        $this->assertFalse($act);
    }

    public function testGivenEmptyEnvThenInitScriptRaisesException(): void
    {
        // Arrange
        $this->prepareEnv();

        // Act
        $act = Service::initScript();

        // Assert
        $this->assertFalse($act);
    }

    public function testGivenEmptyEnvThenInitDaemonRaisesException(): void
    {
        // Arrange
        $this->prepareEnv();

        // Act
        $act = Service::initDaemon();

        // Assert
        $this->assertFalse($act);
    }

    private function prepareEnv(): void
    {
        putenv('NODE_HOST=');
        putenv('NODE_ADDRESS=');
        putenv('NODE_PUBLIC_KEY=');
        putenv('NODE_PRIVATE_KEY=');
    }
}
