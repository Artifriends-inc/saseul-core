<?php

namespace Saseul\Tests\Unit\Custom\Transactions;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Account;
use Saseul\Core\Env;
use Saseul\Custom\Transaction\AbstractTransaction;
use Saseul\Custom\Transaction\CreateToken;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class CreateTokenTest extends TestCase
{
    private $sut;
    private $sutName;
    private $from;
    private $publicKey;
    private $privateKey;
    private $version;
    private $timeStamp;
    private $token_amount;
    private $token_name;
    private $token_publisher;

    protected function setUp(): void
    {
        $this->sut = new CreateToken();
        $this->sutName = 'CreateToken';
        $this->from = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';
        $this->publicKey = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $this->privateKey = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $this->version = '1.0';
        $this->timeStamp = DateTime::Microtime();
        $this->token_amount = 1000;
        Env::$genesis['coin_amount'] = $this->token_amount + 100;
        $this->token_name = 'token name';
        $this->token_publisher = str_repeat(
            '*',
            Account::ADDRESS_SIZE
        );
    }

    public function testSutInheritsAbstractTransaction(): void
    {
        // Assert
        static::assertInstanceOf(AbstractTransaction::class, $this->sut);
    }

    public function testGivenNullTokenAmountThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'token_name' => $this->token_name,
            'token_publisher' => $this->token_publisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenZeroTokenAmountThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => 0,
            'token_name' => $this->token_name,
            'token_publisher' => $this->token_publisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenTokenAmountMoreThanGenesisCoinThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidTokenAmount = Env::$genesis['coin_amount'] + 1000;
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $invalidTokenAmount,
            'token_name' => $this->token_name,
            'token_publisher' => $this->token_publisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenNonStringTokenNameThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidTokenName = 0;
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $this->token_amount,
            'token_name' => $invalidTokenName,
            'token_publisher' => $this->token_publisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenTokenNameMoreThan63ThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidTokenName = str_repeat('*', 64);
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $this->token_amount,
            'token_name' => $invalidTokenName,
            'token_publisher' => $this->token_publisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenNonStringTokenPublisherThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidTokenPublisher = 1004;
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $this->token_amount,
            'token_name' => $this->token_name,
            'token_publisher' => $invalidTokenPublisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    public function testGivenWrongSizeTokenPublisherThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidTokenPublisher = 'wrong size token publisher';
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $this->token_amount,
            'token_name' => $this->token_name,
            'token_publisher' => $invalidTokenPublisher
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }

    private function makeHash($transaction)
    {
        return hash('sha256', json_encode($transaction));
    }

    private function makeSignature($thash)
    {
        return Key::makeSignature($thash, $this->privateKey, $this->publicKey);
    }
}
