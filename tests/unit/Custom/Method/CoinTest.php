<?php

namespace Saseul\Test\Unit\Custom\Method;

use PHPUnit\Framework\TestCase;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Method\Coin;
use Saseul\System\Database;

class CoinTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getCoinCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getCoinCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getCoinCollection()->drop();
    }

    public function testGivenAccountDataThenGetCoinData(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'balance' => 1000,
                'deposit' => 200,
            ],
            [
                'address' => '0x6F1000',
                'balance' => 4000,
                'deposit' => 10,
            ]
        ];
        self::$db->getCoinCollection()->insertMany($saveData);

        // Act
        $actual = Coin::getAll([$address]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);
        $this->assertArrayNotHasKey($saveData[1]['address'], $actual);

        $this->assertSame($saveData[0]['balance'], $actual[$address]['balance']);
    }

    public function testGivenMultiAccountDataThenGetCoinData(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'balance' => 1000,
                'deposit' => 200,
            ],
            [
                'address' => '0x6F1000',
                'balance' => 4000,
                'deposit' => 10,
            ]
        ];
        self::$db->getCoinCollection()->insertMany($saveData);

        // Act
        $actual = Coin::getAll([$address, $saveData[1]['address']]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);
        $this->assertArrayHasKey($saveData[1]['address'], $actual);

        $this->assertSame($saveData[1]['balance'], $actual[$saveData[1]['address']]['balance']);
    }

    public function testGivenFirstTimeAccountDataThenGetCoinData(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();

        // Act
        $actual = Coin::getAll([$address]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);
        $this->assertArrayHasKey('balance', $actual[$address]);
        $this->assertArrayHasKey('deposit', $actual[$address]);
    }
}
