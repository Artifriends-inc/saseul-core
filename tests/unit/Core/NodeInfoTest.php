<?php

namespace Saseul\Test\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;
use Saseul\Core\NodeInfo;

class NodeInfoTest extends TestCase
{
    protected function tearDown(): void
    {
        Env::load();
    }

    public function testGivenEnvWhenIsExistNodeInfoThenTrue(): void
    {
        // Act
        $act = NodeInfo::isExist();

        // Assert
        $this->assertTrue($act);
    }

    public function testGivenEmptyEnvThenFalse(): void
    {
        // Arrange
        Env::$nodeInfo['host'] = '';

        // Act
        $act = NodeInfo::isExist();

        // Assert
        $this->assertFalse($act);
    }

    public function testGetPrivateKey(): void
    {
        // Arrange
        Env::$nodeInfo['private_key'] = 'arti';

        // Act
        $act = NodeInfo::getPrivateKey();

        // Assert
        $this->assertSame(Env::$nodeInfo['private_key'], $act);
    }

    public function testGetPublicKey(): void
    {
        // Arrange
        Env::$nodeInfo['public_key'] = 'arti';

        // Act
        $act = NodeInfo::getPublicKey();

        // Assert
        $this->assertSame(Env::$nodeInfo['public_key'], $act);
    }

    public function testGetHost(): void
    {
        // Arrange
        Env::$nodeInfo['host'] = 'naver';

        // Act
        $act = NodeInfo::getHost();

        // Assert
        $this->assertSame(Env::$nodeInfo['host'], $act);
    }

    public function testGetAddress(): void
    {
        // Arrange
        Env::$nodeInfo['address'] = '127001';

        // Act
        $act = NodeInfo::getAddress();

        // Assert
        $this->assertSame(Env::$nodeInfo['address'], $act);
    }
}
