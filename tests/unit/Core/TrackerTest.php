<?php

namespace Saseul\Test\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Role;
use Saseul\Core\Env;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\Database;

class TrackerTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getTrackerCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTrackerCollection()->drop();
    }

    protected function setUp(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        Env::$genesis['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    }

    protected function tearDown(): void
    {
        Env::$nodeInfo['address'] = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
        self::$db->getTrackerCollection()->drop();
    }

    public function testGivenGenesisAddressThenAddTrackerOnDbReturnGenesisNode(): void
    {
        // Act
        $actual = Tracker::addTrackerOnDb();

        // Assert
        $this->assertSame(Role::VALIDATOR, $actual);
    }

    public function testGivenLightNodeAddressThenAddTrackerOnDbReturnLightNode(): void
    {
        // Arrange
        Env::$nodeInfo['address'] = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';

        // Act
        $actual = Tracker::addTrackerOnDb();

        // Assert
        $this->assertSame(Role::LIGHT, $actual);
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

        self::$db->getTrackerCollection()->insertOne([
            'host' => '',
            'address' => $address,
            'role' => Role::VALIDATOR,
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
        self::$db->getTrackerCollection()->insertMany($hostList);

        // Act
        Tracker::resetBanList();

        // Assert
        $hostOneActual = self::$db->getTrackerCollection()->findOne(
            ['host' => $hostList[0]['host']]
        );
        $hostTwoActual = self::$db->getTrackerCollection()->findOne(
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
        self::$db->getTrackerCollection()->insertOne($hostData);

        // Act
        Tracker::setBanHost($hostData['host']);

        // Assert
        $actual = self::$db->getTrackerCollection()->findOne(['host' => $hostData['host']]);
        $this->assertSame('ban', $actual['status']);
    }

    public function testGivenFakeValidatorAddressThenSetRole(): void
    {
        // Arrange
        $address = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e48';

        // Act
        Tracker::setRole($address, Role::VALIDATOR);

        // Assert
        $actual = self::$db->getTrackerCollection()->findOne(['address' => $address]);
        $this->assertSame($address, $actual['address']);
        $this->assertSame(Role::VALIDATOR, $actual['role']);
    }

    public function testGivenNodeListThenSetHost(): void
    {
        // Arrange
        $nodeListData = [
            [
                'host' => NodeInfo::getHost(),
                'address' => NodeInfo::getAddress(),
                'role' => Role::LIGHT,
                'status' => 'admitted',
            ],
            [
                'host' => '192.168.13.30',
                'address' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e49',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($nodeListData);

        $assertData = [
            [
                'host' => NodeInfo::getHost(),
                'address' => NodeInfo::getAddress(),
            ],
            [
                'host' => '192.168.14.30',
                'address' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e49'
            ],
        ];

        // Act
        Tracker::setHosts($assertData);

        // Assert
        $noChangeActural = self::$db->getTrackerCollection()->findOne(['address' => NodeInfo::getAddress()]);
        $this->assertSame($nodeListData[0]['host'], $noChangeActural['host']);
        $this->assertSame($nodeListData[0]['address'], $noChangeActural['address']);

        $changeActural = self::$db->getTrackerCollection()->findOne(['address' => $assertData[1]['address']]);
        $this->assertSame($assertData[1]['host'], $changeActural['host']);
        $this->assertSame($assertData[1]['address'], $changeActural['address']);
    }

    public function testGivenMyNodeInfoThenSetMyHost(): void
    {
        // Arrange
        $nodeListData = [
            [
                'host' => NodeInfo::getHost(),
                'address' => NodeInfo::getAddress(),
            ],
            [
                'host' => NodeInfo::getHost(),
                'address' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e49'
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($nodeListData);

        // Act
        Tracker::setMyHost();

        // Assert
        $emptyHostActual = self::$db->getTrackerCollection()->findOne([
            'address' => $nodeListData[1]['address'],
        ]);
        $myHostActual = self::$db->getTrackerCollection()->findOne([
            'address' => NodeInfo::getAddress(),
        ]);

        $this->assertSame(NodeInfo::getHost(), $myHostActual['host']);

        $this->assertSame('', $emptyHostActual['host']);
        $this->assertSame($nodeListData[1]['address'], $emptyHostActual['address']);
    }

    public function testGivenNodeInfoDataThenGetRole(): void
    {
        // Arrange
        $firstNode = NodeInfo::getAddress();
        $secondNode = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e49';
        $saveData = [
            [
                'host' => NodeInfo::getHost(),
                'address' => $firstNode,
                'role' => Role::LIGHT,
            ],
            [
                'host' => '192.168.13.30',
                'address' => $secondNode,
                'role' => Role::VALIDATOR,
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($saveData);

        // Act
        $firstNodeRole = Tracker::getRole($firstNode);
        $secondNodeRole = Tracker::getRole($secondNode);

        // Assert
        $this->assertIsString($firstNodeRole);
        $this->assertIsString($secondNodeRole);

        $this->assertSame(Role::LIGHT, $firstNodeRole);
        $this->assertSame(Role::VALIDATOR, $secondNodeRole);
    }

    public function testGivenNodeInfoThenGetRoleReturnLightNode(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'host' => NodeInfo::getHost(),
                'address' => $address,
            ]
        ];
        self::$db->getTrackerCollection()->insertOne($saveData);

        // Act
        $actual = Tracker::getRole($address);

        // Assert
        $this->assertIsString($actual);
        $this->assertSame(Role::LIGHT, $actual);
    }

    public function testGivenNodeInfoThenGetValidatorAddress(): void
    {
        // Arrange
        $this->makeNodeData();

        // Act
        $actual = Tracker::getValidatorAddress();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x6f0001', $actual);
    }

    public function testGivenNodeInfoThenGetSupervisorAddress(): void
    {
        // Arrange
        $this->makeNodeData();

        // Act
        $actual = Tracker::getSupervisorAddress();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x6f0002', $actual);
    }

    public function testGivenNodeInfoThenGetArbiterAddress(): void
    {
        // Arrange
        $this->makeNodeData();

        // Act
        $actual = Tracker::getArbiterAddress();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x6f0003', $actual);
    }

    public function testGivenNodeInfoThenGetsFullNode(): void
    {
        // Arrange
        $this->makeNodeData();

        // Act
        $actual = Tracker::getFullNodeAddress();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x6f0003', $actual);
        $this->assertNotContains('0x6f0004', $actual);
    }

    public function testGivenNodeListThenGetsAccessibleNodeList(): void
    {
        // Arrange
        $insertData = [
            [
                'host' => '10.0.0.1',
                'address' => '0x6f0001',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ],
            [
                'host' => '',
                'address' => '0x6f002',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ],
            [
                'host' => '10.0.0.2',
                'address' => '0x6f0003',
                'role' => Role::ARBITER,
                'status' => 'ban'
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($insertData);

        // Act
        $actual = Tracker::getAccessibleNodeList();

        // Assert
        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $this->assertSame('0x6f0001', $actual[0]['address']);
        $this->assertNotSame('0x6f0002', $actual[0]['address']);
    }

    public function testGivenNodeListThenGetsAccessibleValidatorList(): void
    {
        // Arrange
        $insertData = [
            [
                'host' => '10.0.0.1',
                'address' => '0x6f0001',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ],
            [
                'host' => '',
                'address' => '0x6f002',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ],
            [
                'host' => '10.0.0.2',
                'address' => '0x6f0002',
                'role' => Role::ARBITER,
                'status' => 'ban'
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($insertData);

        // Act
        $actual = Tracker::getAccessibleValidatorList();

        // Assert
        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $this->assertSame('0x6f0001', $actual[0]['address']);
        $this->assertSame(Role::VALIDATOR, $actual[0]['role']);
    }

    public function testGivenBanHostListThenGetsBanList(): void
    {
        // Arrange
        $insertData = [
            [
                'host' => '10.0.0.1',
                'address' => '0x6f0001',
                'role' => Role::VALIDATOR,
                'status' => 'ban',
            ],
            [
                'host' => '',
                'address' => '0x6f0002',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ],
            [
                'host' => '10.0.0.3',
                'address' => '0x6f0003',
                'role' => Role::VALIDATOR,
                'status' => 'admitted',
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($insertData);

        // Act
        $actual = Tracker::getBanList();

        // Assert
        $this->assertIsArray($actual);
        $this->assertSame('0x6f0001', $actual[0]['address']);
        $this->assertSame('ban', $actual[0]['status']);
    }

    private function makeNodeData(): void
    {
        $insertData = [
            [
                'host' => '10.10.10.2',
                'address' => '0x6f0001',
                'role' => Role::VALIDATOR,
            ],
            [
                'host' => '10.10.10.3',
                'address' => '0x6f0002',
                'role' => Role::SUPERVISOR,
            ],
            [
                'host' => '10.10.10.4',
                'address' => '0x6f0003',
                'role' => Role::ARBITER,
            ],
            [
                'host' => '10.10.10.5',
                'address' => '0x6f0004',
                'role' => Role::LIGHT,
            ]
        ];
        self::$db->getTrackerCollection()->insertMany($insertData);
    }
}
