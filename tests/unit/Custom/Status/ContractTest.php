<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\Contract;
use Saseul\System\Database;

class ContractTest extends TestCase
{
    protected static $db;
    protected static $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getContractCollection()->drop();

        self::$sut = new Contract();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getContractCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getContractCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $cid = 'ac1345968';
        $burnCid = 'ac2958';

        self::$db->getContractCollection()->insertOne([
            'cid' => $burnCid,
            'contract' => [
                'chash' => '0x506048'
            ],
            'status' => 'active'
        ]);

        Contract::setContract($cid, ['chash' => '1949599x9d9d', 'timestamp' => 192949499]);
        Contract::setContract($burnCid, ['chash' => '0x506048', 'timestamp' => 194958858]);
        Contract::burnContract($burnCid);

        // Act
        Contract::_save();

        // Assert
        $actualCid = self::$db->getContractCollection()->findOne(['cid' => $cid]);
        $actualBurnCid = self::$db->getContractCollection()->findOne(['cid' => $burnCid]);

        $this->assertSame('active', $actualCid['status']);
        $this->assertSame('burn', $actualBurnCid['status']);
    }

    public function testGivenDataThenLoad(): void
    {
        // Arrange
        Contract::_reset();
        $saveData = [
            [
                'cid' => 'ac-11',
                'contract' => [
                    'chash' => '000001',
                ],
                'status' => 'active',
            ],
            [
                'cid' => 'ac-12',
                'contract' => [
                    'chash' => '000002',
                ],
                'status' => 'active',
            ]
        ];
        self::$db->getContractCollection()->insertMany($saveData);

        Contract::loadContract($saveData[0]['cid']);
        Contract::loadContract($saveData[1]['cid']);
        Contract::burnContract($saveData[1]['cid']);

        // Act
        Contract::_load();

        // Assert
        $allCidList = self::$sut->getAllCidList();
        $allContractList = self::$sut->getAllContractList();
        $allBurnCidList = self::$sut->getAllBurnCidList();

        $this->assertIsArray($allCidList);
        $this->assertIsArray($allContractList);
        $this->assertIsArray($allBurnCidList);

        $this->assertContains($saveData[0]['cid'], $allCidList);
        $this->assertContains($saveData[1]['cid'], $allCidList);

        $this->assertArrayHasKey($saveData[0]['cid'], $allContractList);

        $this->assertContains($saveData[1]['cid'], $allBurnCidList);
    }
}
