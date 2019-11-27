<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Status\Coin;
use Saseul\System\Database;

class CoinTest extends TestCase
{
    protected static $db;
    protected static $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getCoinCollection()->drop();

        self::$sut = new Coin();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getCoinCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getCoinCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x6xaaaaa';

        Coin::setBalance($address, 1000);
        Coin::setDeposit($address, 10);

        // Act
        Coin::_save();

        // Assert
        $actual = self::$db->getCoinCollection()->findOne(['address' => $address]);
        $this->assertSame(1000, $actual['balance']);
        $this->assertSame(10, $actual['deposit']);
    }

    public function testGivenCoinDataThenLoad(): void
    {
        // Arrange
        Coin::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'balance' => 100000000,
                'deposit' => 10000,
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'balance' => 1000,
                'deposit' => 200,
            ]
        ];

        self::$db->getCoinCollection()->insertMany($saveData);

        Coin::loadCoin($address);
        Coin::loadCoin($saveData[1]['address']);

        // Act
        Coin::_load();

        // Assert
        $getAllAddress = self::$sut->getAllAddressList();
        $getAllBalance = self::$sut->getAllBalanceList();
        $getAllDeposit = self::$sut->getAllDepositList();

        $this->assertIsArray($getAllAddress);
        $this->assertIsArray($getAllBalance);
        $this->assertIsArray($getAllDeposit);

        $this->assertArrayHasKey($address, $getAllBalance);
        $this->assertSame($saveData[0]['balance'], $getAllBalance[$saveData[0]['address']]);
        $this->assertArrayHasKey($saveData[1]['address'], $getAllBalance);
        $this->assertSame($saveData[1]['balance'], $getAllBalance[$saveData[1]['address']]);

        $this->assertArrayHasKey($address, $getAllDeposit);
        $this->assertSame($saveData[0]['deposit'], $getAllDeposit[$saveData[0]['address']]);
    }

    public function testGivenNotAddressDataThenLoad(): void
    {
        // Arrange
        Coin::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'balance' => 100000000,
                'deposit' => 10000,
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'balance' => 1000,
                'deposit' => 200,
            ]
        ];

        self::$db->getCoinCollection()->insertMany($saveData);

        // Act
        Coin::_load();

        // Assert
        $this->assertEmpty(self::$sut->getAllAddressList());
        $this->assertEmpty(self::$sut->getAllBalanceList());
        $this->assertEmpty(self::$sut->getAllDepositList());
    }
}
