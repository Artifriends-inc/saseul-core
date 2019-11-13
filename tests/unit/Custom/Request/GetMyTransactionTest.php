<?php

namespace Saseul\Test\Unit\Custom\Request;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetMyTransaction;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Version;

class GetMyTransactionTest extends TestCase
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
        for ($i = 0; $i < 12; $i++) {
            $insertData[] = [
                'thash' => $i,
                'timestamp' => DateTime::Microtime(),
                'public_key' => self::$nodeInfo['public_key'],
            ];
        }
        for ($i = 0; $i < 12; $i++) {
            $insertData[] = [
                'thash' => $i,
                'timestamp' => DateTime::Microtime(),
                'public_key' => '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc5',
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
        $this->sut = new GetMyTransaction();
        $this->sutName = 'GetMyTransaction';
    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $this->sut);
    }

    public function testGivenNoOptionData(): void
    {
        // Arrange
        $this->setInitializeData(0, 0);

        // Act
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertCount(10, $actual);
    }

    public function testGivenCountDataThenReturnSameCount(): void
    {
        // Arrange
        $this->setInitializeData(3, 0);

        // Act
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertCount(3, $actual);
    }

    public function testGivenOffsetDataTheReturnOffsetData(): void
    {
        // Arrange
        $this->setInitializeData(3, 1);

        // Act
        $actual = $this->sut->getResponse();

        // Assert
        $this->assertCount(3, $actual);
        $this->assertSame(11 - 3, $actual[0]['thash']);
    }

    private function setInitializeData(int $limit, int $offset): void
    {
        $request = [
            'type' => $this->sutName,
            'version' => Version::CURRENT,
            'from' => self::$nodeInfo['address'],
            'limit' => (string) $limit,
            'offset' => (string) $offset,
            'timestamp' => DateTime::Microtime()
        ];

        $thash = hash('sha256', json_encode($request, JSON_THROW_ON_ERROR, 512));
        $signature = Key::makeSignature($thash, self::$nodeInfo['private_key'], self::$nodeInfo['public_key']);

        $this->sut->initialize($request, $thash, self::$nodeInfo['public_key'], $signature);
    }
}
