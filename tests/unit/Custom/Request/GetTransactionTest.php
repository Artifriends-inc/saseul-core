<?php

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetTransaction;
use Saseul\System\Key;
use Saseul\System\Database;

class GetTransactionTest extends TestCase
{
    private $sut;
    private $db;

    protected function setUp(): void
    {
        // Arrange
        $this->sut = new GetTransaction();
        $this->db = Database::getInstance();
        $this->db->getTransactionsCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getTransactionsCollection()->drop();
    }

    public function testSutInheritsAbstractRequest()
    {
        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $this->sut);
    }
    public function testFunction(){

        // Arrange
        $transaction = [
            'type' => "GetTransaction",
            'version' => "1.0.0.3",
            'from' => "0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85",
            'timestamp' => 1571383243923406,
            'thash' => "34678a9307ceccd84366e47dedd6906fad2e61fbe7b94ed474d72cbf47f1f7e8",
            'public_key' => "52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3",
            'signature' => "fed50664687217c6bee3abb943335220d46971d6b9f77d40e955a68fc8e3f70a1706e38781b4cca16e6b679160eaca99a6a4294dd846fd4b4471cbc7ef6b5107",
        ];

        $transactionDataList = [
            [
                'thash' => "34678a9307ceccd84366e47dedd6906fad2e61fbe7b94ed474d72cbf47f1f7e8",
                'timestamp' => 1571383243923406,
                'block' => "",
                'public_key' => "52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3",
                'result' => "accept",
                'signature' => "fed50664687217c6bee3abb943335220d46971d6b9f77d40e955a68fc8e3f70a1706e38781b4cca16e6b679160eaca99a6a4294dd846fd4b4471cbc7ef6b5107",
            ],
            [
                'thash' => '226be8bedbb953f382a8252d1190542c09f82f21c86d1c38260e40ed144b62c9',
                'timestamp' => 1571383243552288,
                'block' => '',
                'public_key' => '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3',
                'result' => 'accept',
                'signature' => 'd8b36cdc01ef5ee4fff0d9ad5b2a48222ba3e267b810ba3d7d50a13f9623409d65a6c8988df15979ba3b3f77ad33629363a7f75666dc84b3eb70a1bfaf505c01'
            ]
        ];

        $this->db->getTransactionsCollection()->insertMany($transactionDataList);

        $thash = hash('sha256', json_encode($transaction));
        $privateKey = 'a745fbb3860f243293a66a5fcadf70efc1fa5fa5f0254b3100057e753ef0d9bb';
        $publicKey = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $privateKey, $publicKey);
        $this->sut->initialize($transaction, $thash, $publicKey, $signature);

        // Act
        $result = $this->sut->getResponse();

        //Assert
        $this->assertSame($transaction['thash'], $result['thash']);
    }
}
