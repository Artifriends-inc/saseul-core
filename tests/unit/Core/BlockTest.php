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

    public function testGivenBlockDataThenGetsLastBlock(): void
    {
        // Arrange
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

        // Act
        $actual = Block::getLastBlock();

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('block_number', $actual);
        $this->assertArrayHasKey('blockhash', $actual);
        $this->assertSame($insertData[1]['blockhash'], $actual['blockhash']);
    }
}
