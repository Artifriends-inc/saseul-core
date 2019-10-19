<?php

namespace Saseul\Tests\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\Contract;
use Saseul\System\Database;

class ContractTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->getContractCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getContractCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $cid = 'ac1345968';
        $burnCid = 'ac2958';

        $this->db->getContractCollection()->insertOne([
            'cid' => $burnCid,
            'contract' => [
                'chash' => '0x506048'
            ],
            'status' => 'active'
        ]);

        Contract::SetContract($cid, ['chash' => '1949599x9d9d', 'timestamp' => 192949499]);
        Contract::SetContract($burnCid, ['chash' => '0x506048', 'timestamp' => 194958858]);
        Contract::BurnContract($burnCid);

        // Act
        Contract::_Save();

        // Assert
        $actualCid = $this->db->getContractCollection()->findOne(['cid' => $cid]);
        $actualBurnCid = $this->db->getContractCollection()->findOne(['cid' => $burnCid]);
        $this->assertSame('active', $actualCid['status']);
        $this->assertSame('burn', $actualBurnCid['status']);
    }
}
