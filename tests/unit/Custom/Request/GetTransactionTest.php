<?php

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetTransaction;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Version;

class GetTransactionTest extends TestCase
{
    protected static $db;
    protected static $nodeInfo;
    private $sut;
    private $sutName;

    public static function setUpBeforeClass(): void
    {
        Env::load();
        self::$nodeInfo = Env::$nodeInfo;

        self::$db = Database::getInstance();
        self::$db->getTransactionsCollection()->drop();

        $insertData = [];
        for ($i = 0; $i < 5; $i++) {
            $insertData[] = [
                'thash' => "saseul_{$i}",
                'timestamp' => \Saseul\Util\DateTime::Microtime(),
                'public_key' => self::$nodeInfo['public_key'],
            ];
        }
        self::$db->getTransactionsCollection()->insertMany($insertData);
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTransactionsCollection()->drop();
    }

    protected function setUp(): void
    {
        $this->sut = new GetTransaction();
        $this->sutName = 'GetTransaction';
    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $this->sut);
    }

    public function testGivenDataThenReturnSameData(): void
    {
        // Arrange
        $this->setInitializeData('saseul_2');

        // Act
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertSame('saseul_2', $actual['thash']);
        $this->assertSame(self::$nodeInfo['public_key'], $actual['public_key']);
    }

    private function setInitializeData(string $thash): void
    {
        $request = [
            'type' => $this->sutName,
            'version' => Version::CURRENT,
            'from' => self::$nodeInfo['address'],
            'thash' => $thash,
            'timestamp' => \Saseul\Util\DateTime::Microtime(),
        ];

        $thash = hash('sha256', json_encode($request, JSON_THROW_ON_ERROR, 512));
        $signature = Key::makeSignature($thash, self::$nodeInfo['private_key'], self::$nodeInfo['public_key']);

        $this->sut->initialize($request, $thash, self::$nodeInfo['public_key'], $signature);
    }
}
