<?php

namespace Saseul\Tests\Unit\Custom\Transactions;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Account;
use Saseul\Custom\Status\Token;
use Saseul\Custom\Transaction\AbstractTransaction;
use Saseul\Custom\Transaction\SendToken;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class SendTokenTest extends TestCase
{
    private $sut;
    private $sutName;
    private $from;
    private $publicKey;
    private $privateKey;
    private $version;
    private $timeStamp;
    private $to;
    private $amount;

    protected function setUp(): void
    {
        $this->sut = new SendToken();
        $this->sutName = 'SendToken';
        $this->from = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';
        $this->publicKey = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $this->privateKey = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $this->version = '1.0';
        $this->timeStamp = DateTime::Microtime();
        $this->to = str_repeat('*', Account::ADDRESS_SIZE);
        $this->amount = 1000;
    }

    public function testSutInheritsAbstractTransaction(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractTransaction::class, $this->sut);
    }

    public function testGivenNullToThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'amount' => $this->amount
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNonStringToThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => 1004,
            'amount' => $this->amount
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenWrongSizeToThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => 'wrong size to',
            'amount' => $this->amount
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNullAmountThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNonNumericAmountThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => 'string amount'
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature($thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNegativeAmountThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => -10
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenAmountZeroThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => 0,
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenSameFromAndToThenEqualsTokenBalanceFromAndTo(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->from,
            'amount' => $this->amount,
            'token_name' => 'SendTokenTest'
        ];
        $thash = hash('sha256', json_encode($transaction));
        $signature = Key::makeSignature(
            $thash,
            $this->privateKey,
            $this->publicKey
        );
        $this->sut->initialize(
            $transaction,
            $thash,
            $this->publicKey,
            $signature
        );

        $this->sut->getStatus();

        // Act
        $this->sut->setStatus();

        // Assert
        $this->assertSame(Token::GetBalance($transaction['from'], 'SendTokenTest'),
            Token::GetBalance($transaction['to'], 'SendTokenTest'));
    }
}
