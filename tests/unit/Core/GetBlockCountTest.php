<?php

use PHPUnit\Framework\TestCase;
use Saseul\Core\Block;
use Saseul\System\Database;

class GetBlockCountTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getBlocksCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getBlocksCollection()->drop();
    }

    public function testGivenEmptyDataThenCountBlockReturnZero(): void
    {
        // Act
        $blockCount = Block::getCount();

        // Assert
        $this->assertSame(0, $blockCount);
    }

    public function testGivenBlockDataThenCountBlock(): void
    {
        // Arrange
        $insertData = [
            [
                'block_number' => 1,
                'last_blockhash' => '',
                'blockhash' => 'de8d0c3b9d378118e5542dc8cb2090abbdc08348c895725b33c5a38ce772fa79',
                'transaction_count' => 2,
                's_timestamp' => 1562121069000000,
                'timestamp' => 1562121077974200,
            ],
            [
                'block_number' => 2,
                'last_blockhash' => 'de8d0c3b9d378118e5542dc8cb2090abbdc08348c895725b33c5a38ce772fa79',
                'blockhash' => '21896be935e930aa4f873f6806bb6303777be02c14ff3bcb36c82e08b51383e9',
                'transaction_count' => 1,
                's_timestamp' => 1562121306000000,
                'timestamp' => 1562121309367785,
            ]
        ];
        self::$db->getBlocksCollection()->insertMany($insertData);

        // Act
        $blockCount = Block::getCount();

        // Assert
        $this->assertSame(2, $blockCount);
    }
}
