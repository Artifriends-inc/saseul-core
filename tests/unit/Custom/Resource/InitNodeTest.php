<?php

namespace Saseul\tests\Custom\Resource;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rank;
use Saseul\Core\Env;
use Saseul\Custom\Resource\InitNode;
use Saseul\System\Database;

class InitNodeTest extends TestCase
{
    private $sut;

    protected function setUp(): void
    {
        $this->sut = new InitNode();
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        Env::$genesis['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    }

    protected function tearDown(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        $db = Database::getInstance();
        $db->bulk->delete([]);
        $db->BulkWrite(MongoDb::NAMESPACE_TRACKER);
    }

    public function testGivenGenesisNodeAddressThenProcessCheck(): void
    {
        // Act
        $this->sut->process();
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertSame(Rank::VALIDATOR, $actual['role']);
    }

    public function testGivenLightNodeAddressThenProcessCheck(): void
    {
        // Arrange
        Env::$nodeInfo['address'] = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';

        // Act
        $this->sut->process();
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertSame(Rank::LIGHT, $actual['role']);
    }
}
