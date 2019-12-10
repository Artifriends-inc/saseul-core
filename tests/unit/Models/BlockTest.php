<?php

namespace Saseul\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Saseul\DataAccess\Models\Block;

class BlockTest extends TestCase
{
    private $sut;

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

    public function testGivenBlockDataThenSetsAttribute(): void
    {
        // Arrange
        $blockData = [
            'block_number' => 1,
            'last_blockhash' => '0001',
            'blockhash' => '0002',
            'transaction_count' => 0,
            's_timestamp' => 1000,
            'timestamp' => 10000,
        ];

        // Act
        $this->sut->setAttributeUseObject((object) $blockData);

        // Assert
        $this->assertSame($blockData, $this->sut->getArray());
    }

    public function testGivenNullDataThenSetsDefaultAttribute(): void
    {
        // Act
        $this->sut->setAttributeUseObject((object) null);

        // Assert
        $this->assertSame(0, $this->sut->getBlockNumber());
        $this->assertSame('', $this->sut->getBlockHash());
    }

    public function testGivenBlockDataThenGetsArrayData(): void
    {
        // Arrange
        $blockData = [
            'block_number' => 1,
            'last_blockhash' => '0001',
            'blockhash' => '0002',
            'transaction_count' => 0,
            's_timestamp' => 1000,
            'timestamp' => 10000,
        ];
        $this->sut->setAttributeUseObject((object) $blockData);

        // Act
        $actual = $this->sut->getArray();

        // Assert
        $this->assertIsArray($actual);
        $this->assertSame($blockData, $actual);
    }
}
