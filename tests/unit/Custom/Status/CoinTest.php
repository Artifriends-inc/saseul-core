<?php

namespace Saseul\Tests\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\Coin;
use Saseul\System\Database;

class CoinTest extends TestCase
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

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x6xaaaaa';

        Coin::SetBalance($address, 1000);
        Coin::SetDeposit($address, 10);

        // Act
        Coin::_Save();

        // Assert
        $actual = $this->db->getCoinCollection()->findOne(['address' => $address]);
        $this->assertSame(1000, $actual['balance']);
        $this->assertSame(10, $actual['deposit']);
    }
}
