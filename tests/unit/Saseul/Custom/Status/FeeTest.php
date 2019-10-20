<?php

namespace Saseul\Tests\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\Fee;
use Saseul\System\Database;

class FeeTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->getCoinCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getCoinCollection()->drop();
    }

    public function testGivenNodeListThenSetBalance(): void
    {
        // Arrange
        $address = '0x6aaaaa';

        $this->db->getCoinCollection()->insertOne([
            'address' => $address,
            'balance' => 1000,
        ]);

        $nodeInfo = [
            [
                'address' => $address,
                'balance' => 1010
            ]
        ];

        // Act
        Fee::setBalance($nodeInfo);

        // Assert
        $actual = $this->db->getCoinCollection()->findOne(['address' => $address]);
        $this->assertSame(1010, $actual['balance']);
    }
}
