<?php

namespace Saseul\Common;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saseul\Custom\Transaction\AbstractTransaction;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class AbstractTransactionTest extends TestCase
{
    private $sut;
    private $sutName;
    private $from;
    private $publicKey;
    private $privateKey;
    private $version;
    private $timeStamp;

    protected function setUp(): void
    {
        $this->sut = $this->getMockForAbstractClass(AbstractTransaction::class);
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
        $this->from = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';
        $this->publicKey = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $this->privateKey = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $this->version = '1.0';
        $this->timeStamp = DateTime::Microtime();
    }

    public function testSutIsAbstractClass(): void
    {
        // Arrange
        $sut = new ReflectionClass(AbstractTransaction::class);

        // Assert
        static::assertTrue($sut->isAbstract());
    }

    public function testGivenNonVersionThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'timestamp' => $this->timeStamp
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenInvalidTypeThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => 'invalidTransaction',
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'timestamp' => $this->timeStamp
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenNonTimestampThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange/
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenInvalidFromThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => '0x000000000000000000000000000000000000000000000',
            'version' => $this->version,
            'timestamp' => $this->timeStamp
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenInvalidSignatureThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp
        ];
        $thash = hash('sha256', json_encode($transaction));
        $invalidPrivate_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff';
        $signature = Key::makeSignature($thash, $invalidPrivate_key, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenValidTransactionThenGetValidityMethodReturnsTrue(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertTrue($actual);
    }
}
