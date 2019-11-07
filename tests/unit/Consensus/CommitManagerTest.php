<?php

namespace Saseul\Tests\Unit\Consensus;

use PHPUnit\Framework\TestCase;
use Saseul\Consensus\CommitManager;
use Saseul\System\Database;

class CommitManagerTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();

        // Test DB cleanup
        $this->db->getTransactionsCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getTransactionsCollection()->drop();
    }

    /**
     * 입력된 Transaction 에 Block hash 값을 추가하여 준다.
     */
    public function testGivenTransactionsDataThenCommitTransaction(): void
    {
        // Arrange
        $transactionDataList = [
            [
                'thash' => '226be8bedbb953f382a8252d1190542c09f82f21c86d1c38260e40ed144b62c9',
                'timestamp' => 1571383243552288,
                'block' => '',
                'public_key' => '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3',
                'result' => 'accept',
                'signature' => 'd8b36cdc01ef5ee4fff0d9ad5b2a48222ba3e267b810ba3d7d50a13f9623409d65a6c8988df15979ba3b3f77ad33629363a7f75666dc84b3eb70a1bfaf505c01',
            ],
            [
                'thash' => '34678a9307ceccd84366e47dedd6906fad2e61fbe7b94ed474d72cbf47f1f7e8',
                'timestamp' => 1571383243923406,
                'block' => '',
                'public_key' => '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3',
                'result' => 'accept',
                'signature' => 'fed50664687217c6bee3abb943335220d46971d6b9f77d40e955a68fc8e3f70a1706e38781b4cca16e6b679160eaca99a6a4294dd846fd4b4471cbc7ef6b5107'
            ]
        ];
        $this->db->getTransactionsCollection()->insertMany($transactionDataList);

        $blockData = [
            'block_number' => 1,
            'loast_blockhash' => '',
            'blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'transaction_count' => 2,
            's_timestamp' => 1571383244000000,
            'timestamp' => 1571383245784173,
        ];

        $commitManager = new CommitManager();

        // Act
        $commitManager->commitTransaction($transactionDataList, $blockData);

        // Assert
        $updateTransaction = $this->db->getTransactionsCollection()->findOne([
            'thash' => '226be8bedbb953f382a8252d1190542c09f82f21c86d1c38260e40ed144b62c9'
        ]);
        $this->assertSame($blockData['blockhash'], $updateTransaction['block']);
    }
}
