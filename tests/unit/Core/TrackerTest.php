<?php


use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Rank;
use Saseul\Core\Env;
use Saseul\Core\Tracker;
use PHPUnit\Framework\TestCase;
use Saseul\System\Database;

class TrackerTest extends TestCase
{
    protected function setUp(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        Env::$genesis['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    }

    protected function tearDown(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        $db = Database::GetInstance();
        $db->bulk->delete([]);
        $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
    }

    public function testGivenGenesisAddressThenAddTrackerOnDbReturnGenesisNode(): void
    {
        // Act
        $actual = Tracker::addTrackerOnDb();

        // Assert
        $this->assertSame(Rank::VALIDATOR, $actual);
    }

    public function testGivenLightNodeAddressThenAddTrackerOnDbReturnLightNode(): void
    {
        // Arrange
        Env::$nodeInfo['address'] = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';

        // Act
        $actual = Tracker::addTrackerOnDb();

        // Assert
        $this->assertSame(Rank::LIGHT, $actual);
    }
}
