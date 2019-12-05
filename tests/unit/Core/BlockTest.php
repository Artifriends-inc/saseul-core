<?php

namespace Saseul\Test\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Block;
use Saseul\System\Database;

class BlockTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getBlocksCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getBlocksCollection()->drop();
    }

    protected function setUp(): void
    {
        $insertData = [
            [
                'block_number' => 1,
                'last_blockhash' => '0001',
                'blockhash' => '0002',
                'transaction_count' => 0,
                's_timestamp' => 1000,
                'timestamp' => 10000,
            ],
            [
                'block_number' => 2,
                'last_blockhash' => '0002',
                'blockhash' => '0003',
                'transaction_count' => 2,
                's_timestamp' => 2000,
                'timestamp' => 20000,
            ]
        ];
        self::$db->getBlocksCollection()->insertMany($insertData);
    }

    protected function tearDown(): void
    {
        self::$db->getBlocksCollection()->drop();
    }

    public function testGivenBlockDataThenGetsLastBlock(): void
    {
        // Act
        $actual = Block::getLastBlock();

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('block_number', $actual);
        $this->assertArrayHasKey('blockhash', $actual);
        $this->assertSame('0003', $actual['blockhash']);
    }

    public function testGivenBlockNumberThenGetsBlockInfo(): void
    {
        // Act
        $actual = Block::getBlockInfoByNumber(2);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('block_number', $actual);
        $this->assertSame(2, $actual['block_number']);
        $this->assertSame('0003', $actual['blockhash']);
    }

    public function testGivenNotSetBlockNumberThenGetsBlockInfoReturnDefaultValue(): void
    {
        // Act
        $actual = Block::getBlockInfoByNumber(5);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('block_number', $actual);
        $this->assertSame(0, $actual['block_number']);
    }

    public function testGivenBlockCountThenGetsLatestBlockDataList(): void
    {
        // Act
        $actual = Block::getLatestBlockList(1);

        // Assert
        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $this->assertArrayNotHasKey('_id', $actual[0]);
        $this->assertSame(2, $actual[0]['block_number']);
        $this->assertSame('0003', $actual[0]['blockhash']);
        $this->assertNotSame(1, $actual[0]['block_number']);
        $this->assertNotSame('0002', $actual[0]['block_number']);
    }

    public function testGivenBlockCountThenGetsLatestTransactionDataList(): void
    {
        // Arrange
        $insertData = [
            [
                'thash' => '0001',
                'timestamp' => 10,
                'block' => 1,
                'public_key' => '0x6f0001',
                'result' => 'accept',
                'signature' => 'f001',
                'transaction' => [],
            ],
            [
                'thash' => '0002',
                'timestamp' => 20,
                'block' => 2,
                'public_key' => '0x6f0001',
                'result' => 'reject',
                'signature' => 'f002',
                'transaction' => [
                    'data' => 20000
                ],
            ]
        ];
        self::$db->getTransactionsCollection()->insertMany($insertData);

        // Act
        $actual = Block::getLatestTransactionList(1);

        // Assert
        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $this->assertSame('0002', $actual[0]['thash']);
        $this->assertSame('reject', $actual[0]['result']);
        $this->assertNotSame('0001', $actual[0]['thash']);
        $this->assertNotSame('f001', $actual[0]['signature']);
        $this->assertIsArray($actual[0]['transaction']);
        $this->assertSame(20000, $actual[0]['transaction']['data']);
    }
}
