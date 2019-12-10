<?php

namespace Saseul\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Saseul\DataAccess\Models\Transaction;
use Saseul\System\Database;

class TransactionTest extends TestCase
{
    protected static $db;
    private $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getTransactionsCollection()->drop();

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
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTransactionsCollection()->drop();
    }

    protected function setUp(): void
    {
        $this->sut = new Transaction();
    }

    public function testTransactionHasTransactionHashProperty(): void
    {
        $this->assertClassHasAttribute(
            'transactionHash',
            Transaction::class,
            'Transaction model class does not have transaction hash property.'
        );
    }

    public function testTransactionHasTimestampProperty(): void
    {
        $this->assertClassHasAttribute(
            'timestamp',
            Transaction::class,
            'Transaction model class does not have timestamp property.'
        );
    }

    public function testTransactionHasBlockHashProperty(): void
    {
        $this->assertClassHasAttribute(
            'blockHash',
            Transaction::class,
            'Transaction model class does not have block hash property.'
        );
    }

    public function testTransactionHasPublicKeyProperty(): void
    {
        $this->assertClassHasAttribute(
            'publicKey',
            Transaction::class,
            'Transaction model class does not have public key property.'
        );
    }

    public function testTransactionHasResultProperty(): void
    {
        $this->assertClassHasAttribute(
            'result',
            Transaction::class,
            'Transaction model class does not have result property.'
        );
    }

    public function testTransactionHasSignatureProperty(): void
    {
        $this->assertClassHasAttribute(
            'signature',
            Transaction::class,
            'Transaction model class does not have signature property.'
        );
    }

    public function testTransactionHasTransactionDataProperty(): void
    {
        $this->assertClassHasAttribute(
            'transactionData',
            Transaction::class,
            'Transaction model class does not have transaction data property.'
        );
    }

    public function testGivenTransactionDataThenSetsAttribute(): void
    {
        // Arrange
        $data = [
            'thash' => '0001',
            'timestamp' => 10,
            'block' => 1,
            'public_key' => '0x6f0001',
            'result' => 'accept',
            'signature' => 'f001',
            'transaction' => [
                'data' => 20000
            ],
        ];

        // Act
        $this->sut->setAttributeUseObject((object) $data);

        // Assert
        $this->assertSame($data['thash'], $this->sut->getTransactionHash());
        $this->assertSame($data['transaction'], $this->sut->getTransactionData());
    }

    public function testGivenNullDataThenSetsDefaultAttribute(): void
    {
        // Act
        $this->sut->setAttributeUseObject((object) null);

        // Assert
        $this->assertSame('', $this->sut->getTransactionHash());
        $this->assertSame([], $this->sut->getTransactionData());
        $this->assertSame(0, $this->sut->getTimestamp());
    }

    public function testGivenTransactionDataThenGetsTransactionListData(): void
    {
        // Arrange
        $data = [
            'thash' => '0001',
            'timestamp' => 10,
            'block' => 1,
            'public_key' => '0x6f0001',
            'result' => 'accept',
            'signature' => 'f001',
            'transaction' => [],
        ];
        $this->sut->setAttributeUseObject((object) $data);

        // Act
        $actual = $this->sut->getArray();

        // Assert
        $this->assertIsArray($actual);
        $this->assertSame($data, $actual);
    }
}
