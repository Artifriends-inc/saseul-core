<?php

namespace Saseul\Tests\Unit\Custom\Transactions;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;
use Saseul\Custom\Transaction\AbstractTransaction;
use Saseul\Custom\Transaction\Logging;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Version;

class LoggingTest extends TestCase
{
    protected static $nodeInfo;

    private $sut;
    private $sutName;

    public static function setUpBeforeClass(): void
    {
        Env::load();
        self::$nodeInfo = Env::$nodeInfo;
    }

    protected function setUp(): void
    {
        $this->sut = new Logging();
        $this->sutName = 'Logging';
    }

    public function testSutAbstractTransaction(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractTransaction::class, $this->sut);
    }

    public function testGivenNonStringUserThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transactional_data = [
            'type' => $this->sutName,
            'from' => self::$nodeInfo['address'],
            'version' => Version::CURRENT,
            'timestamp' => DateTime::Microtime(),
            'address' => 1004,
            'message_type' => 'testLogging',
            'message' => 'Wrong user address',
        ];
        $this->setInitializeData($transactional_data);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenWrongSizeUserThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transactional_data = [
            'type' => $this->sutName,
            'from' => self::$nodeInfo['address'],
            'version' => Version::CURRENT,
            'timestamp' => DateTime::Microtime(),
            'address' => 'wrong size to',
            'message_type' => 'testLogging',
            'message' => 'Wrong user address',
        ];
        $this->setInitializeData($transactional_data);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    private function setInitializeData(array $transaction): void
    {
        $thash = hash('sha256', json_encode($transaction, JSON_THROW_ON_ERROR, 512));
        $signature = Key::makeSignature($thash, self::$nodeInfo['private_key'], self::$nodeInfo['public_key']);
        $this->sut->initialize($transaction, $thash, self::$nodeInfo['public_key'], $signature);
    }
}
