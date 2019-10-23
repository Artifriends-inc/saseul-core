<?php

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetTransaction;

use Saseul\System\Database;
use Saseul\Util\Parser;

class GetTransactionTest extends TestCase
{
    private $find_thash;
    private $request;
    private $db;
    private $rs;
    private $transaction;

    public function setUp(): void
    {
        //Arrange
        $this->request = [
            [
                '_id' => "5da967cd83f3af8c1e6fd9ac",
                'thash' => "226be8bedbb953f382a8252d1190542c09f82f21c86d1c38260e40ed144b62c9",
                'timestamp' => 1571383243552288,
                'block' => "b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903",
                'public_key' => "52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3",
                'result' =>  "accept",
                'signature' => "d8b36cdc01ef5ee4fff0d9ad5b2a48222ba3e267b810ba3d7d50a13f9623409d65a6c8988df15979ba3b3f77ad33629363a7f75666dc84b3eb70a1bfaf505c01",
                'transaction' => [
                    'type' => "Genesis",
                    'version' => "1.0.0.3",
                    'from' => "0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85",
                    'amount' => "1000000000000000",
                    'transaction_data' => [
                        'genesis_message' => "Imagine Beyond and Invent Whatever, Wherever - 
                        Published by ArtiFriends. Thank you for help - YJ.Lee, JW.Lee, SH.Shin, 
                        YS.Han, WJ.Choi, DH.Kang, HG.Lee, KH.Kim, HK.Lee, JS.Han, SM.Park, 
                        SJ.Chae, YJ.Jeon, KM.Lee, JH.Kim, mika, ashal, datalater, namedboy, 
                        masterguru9, ujuc, johngrib, kimpi, greenmon, HS.Lee, TW.Nam, EH.Park, MJ.Mok",
                        'special_thanks' => "Michelle, Francis, JS.Han, Pang, Jeremy, JG, TY.Lee, SH.Ji, HK.Lim, IS.Choi, CH.Park, SJ.Park, DH.Shin and CK.Park",
                        'etc_messages' => [
                            [
                                'writer' => "Michelle.Kim",
                                'message' => "I love jjal."
                            ],
                            [
                                'writer' => "Francis.W.Han",
                                'message' => "khan@artifriends.com, I'm here with JG and SK."
                            ],
                            [
                                'writer' => "JG.Lee",
                                'message' => "In the beginning God created the blocks and the chains. God said, 'Let there be SASEUL' and saw that it was very good."
                            ],
                            [
                                'writer' => "namedboy",
                                'message' => "This is 'SASEUL', Welcome to new world."
                            ],
                            [
                                'writer' => "ujuc",
                                'message' => "Hello Saseul! :)"
                            ]
                        ]
                    ],
                    "timestamp" => 1571383243552288
                ]
            ],
            [
                '_id' => "5da967cd83f3af8c1e6fd9ad",
                'thash' => "34678a9307ceccd84366e47dedd6906fad2e61fbe7b94ed474d72cbf47f1f7e8",
                'timestamp' => 1571383243923406,
                'block' => "b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903",
                'public_key' => "52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3",
                'result' =>  "accept",
                'signature' => "fed50664687217c6bee3abb943335220d46971d6b9f77d40e955a68fc8e3f70a1706e38781b4cca16e6b679160eaca99a6a4294dd846fd4b4471cbc7ef6b5107",
                'transaction' => [
                    'type' => "Deposit",
                    'version' => "1.0.0.3",
                    'from' => "0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85",
                    'amount' => "200000000000000",
                    'fee' => 0,
                    'transaction_data' => "Genesis deposit",
                    "timestamp" => 1571383243923406
                ]
            ]
        ];

        $this->find_thash = ['thash' => "226be8bedbb953f382a8252d1190542c09f82f21c86d1c38260e40ed144b62c9"];

        $this->db = Database::getInstance();

        // Test DB cleanup
        $this->db->getTransactionsCollection()->drop();

        $this->transaction = [];
        $this->db->getTransactionsCollection()->insertMany($this->request);
        $this->rs = $this->db->getTransactionsCollection()->find($this->find_thash);
    }


    public function test_getResponse(): void
    {
        //Act
        foreach ($this->rs as $item){
            $item = Parser::objectToArray($item);
            unset($item['_id']);

            $this->transaction= $item;

            break;
        }

        // Assert
        $this->assertSame($this->transaction['thash'], $this->find_thash['thash']);
    }
}
