<?php

namespace Saseul\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\MongoDb;
use Saseul\Models\Block;
use Saseul\System\Database;

class BlockTest extends TestCase
{
    protected static $db;
    private $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getBlocksCollection()->drop();

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

    public static function tearDownAfterClass(): void
    {
        self::$db->getBlocksCollection()->drop();
    }

    protected function setUp(): void
    {
        $this->sut = new Block();
    }

    public function testBlockHasBlockNumberProperty(): void
    {
        $this->assertClassHasAttribute(
            'blockNumber',
            Block::class,
            'Block model class does not have block number property.'
        );
    }

    public function testBlockHasLastBlockHashProperty(): void
    {
        $this->assertClassHasAttribute(
            'lastBlockHash',
            Block::class,
            'Block model class does not have last block hash property.'
        );
    }

    public function testBlockHasBlockHashProperty(): void
    {
        $this->assertClassHasAttribute(
            'blockHash',
            Block::class,
            'Block model class does not have block hash property.'
        );
    }

    public function testBlockHasTransactionCountProperty(): void
    {
        $this->assertClassHasAttribute(
            'transactionCount',
            Block::class,
            'Block model class does not have transaction count property.'
        );
    }

    public function testBlockHasStandardTimestampProperty(): void
    {
        $this->assertClassHasAttribute(
            'standardTimestamp',
            Block::class,
            'Block model class does not have standard timestamp property.'
        );
    }

    public function testBlockHasTimestampProperty(): void
    {
        $this->assertClassHasAttribute(
            'timestamp',
            Block::class,
            'Block model class does not have timestamp property.'
        );
    }

    public function testGivenQueryDataThenFindOneData(): void
    {
        // Arrange
        $filter = ['block_number' => 2];

        // Act
        $actual = $this->sut->findOne($filter);

        // Assert
        $this->assertIsArray($actual);
        $this->assertSame(2, $actual['block_number']);
        $this->assertSame(20000, $actual['timestamp']);
    }

    public function testGivenQeuryDataThenFindListData(): void
    {
        // Arrange
        $filter = [];
        $option = [
            'sort' => ['timestamp' => MongoDb::DESC],
            'limit' => 1,
        ];

        // Act
        $actual = $this->sut->find($filter, $option);

        // Assert
        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $this->assertSame(2, $actual[0]['block_number']);
        $this->assertSame(20000, $actual[0]['timestamp']);
    }
}
