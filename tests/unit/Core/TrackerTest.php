<?php

namespace Saseul\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Rank;
use Saseul\Constant\Role;
use Saseul\Core\Env;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\Database;

class TrackerTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();

        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        Env::$genesis['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    }

    protected function tearDown(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        $this->db->getTrackerCollection()->drop();
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

    public function testGivenNotValidatorNodeAddressThenIsValidatorReturnFalse(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();

        // Act
        $actual = Tracker::isValidator($address);

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenValidatorNodeAddressThenIsValidatorReturnTrue(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();

        $this->db->getTrackerCollection()->insertOne([
            'host' => '',
            'address' => $address,
            'rank' => Role::VALIDATOR,
            'status' => 'admitted',
        ]);

        // Act
        $actual = Tracker::isValidator($address);

        // Assert
        $this->assertTrue($actual);
    }

    public function testGivenBanListThenResetBanList(): void
    {
        // Arrange
        $hostList = [
            [
                'host' => '192.168.10.41',
                'status' => 'ban',
            ],
            [
                'host' => '192.168.10.42',
                'status' => 'ban',
            ],
            [
                'host' => '192.168.10.43',
                'status' => 'admitted',
            ]
        ];
        $this->db->getTrackerCollection()->insertMany($hostList);

        // Act
        Tracker::resetBanList();

        // Assert
        $hostOneActual = $this->db->getTrackerCollection()->findOne(
            ['host' => $hostList[0]['host']]
        );
        $hostTwoActual = $this->db->getTrackerCollection()->findOne(
            ['host' => $hostList[1]['host']]
        );
        $this->assertSame('admitted', $hostOneActual['status']);
        $this->assertSame('admitted', $hostTwoActual['status']);
    }

    public function testGivenHostListThenBanHost(): void
    {
        // Arrange
        $hostData = [
            'host' => '192.168.10.41',
            'status' => 'admitted'
        ];
        $this->db->getTrackerCollection()->insertOne($hostData);

        // Act
        Tracker::banHost($hostData['host']);

        // Assert
        $actual = $this->db->getTrackerCollection()->findOne(['host' => $hostData['host']]);
        $this->assertSame('ban', $actual['status']);
    }

    public function testGivenFakeValidatorAddressThenSetRank(): void
    {
        // Arrange
        $address = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e48';

        // Act
        Tracker::setRank($address, Role::VALIDATOR);

        // Assert
        $actual = $this->db->getTrackerCollection()->findOne(['address' => $address]);
        $this->assertSame($address, $actual['address']);
        $this->assertSame(Role::VALIDATOR, $actual['rank']);
    }
}
