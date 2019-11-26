<?php

namespace Saseul\Test\Unit\Custom\Transactions;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Account;
use Saseul\Core\Env;
use Saseul\Custom\Status\Coin;
use Saseul\Custom\Transaction\AbstractTransaction;
use Saseul\Custom\Transaction\SendCoin;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class SendCoinTest extends TestCase
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
    private $fee;

    protected function setUp(): void
    {
        $this->sut = new SendCoin();
        $this->sutName = 'SendCoin';
        $this->from = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';
        $this->publicKey = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $this->privateKey = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $this->version = '1.0';
        $this->timeStamp = DateTime::Microtime();
        $this->to = str_repeat('*', Account::ADDRESS_SIZE);
        $this->amount = 1000;
        Env::$genesis['coin_amount'] = $this->amount + 100;
        $this->fee = 100;
    }

    public function testSutAbstractTransaction(): void
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
            'amount' => $this->amount,
            'fee' => $this->fee
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
            'amount' => $this->amount,
            'fee' => $this->fee
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
            'amount' => $this->amount,
            'fee' => $this->fee
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
            'to' => $this->to,
            'fee' => $this->fee
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
            'amount' => 'string amount',
            'fee' => $this->fee
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
            'amount' => -10,
            'fee' => $this->fee
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
            'fee' => $this->fee
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

    public function testGivenNullFeeThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
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

    public function testGivenNonNumericFeeThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => $this->amount,
            'fee' => 'string fee'
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

    public function testGivenNegativeFeeThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => $this->amount,
            'fee' => -1
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

    public function testGivenAmountMoreThanGenesisCoinThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $invalidAmount = Env::$genesis['coin_amount'] + 1000;
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => $invalidAmount,
            'fee' => $this->fee
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

    public function testGivenValidTransactionThenGetValidityReturnsTrue(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->to,
            'amount' => $this->amount,
            'fee' => $this->fee
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
        $this->assertTrue($actual);
    }

    public function testGivenSameFromAndToThenEqualsCoinBalanceFromAndTo(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'to' => $this->from,
            'amount' => $this->amount,
            'fee' => $this->fee
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
        $this->assertSame(Coin::getBalance($transaction['from']), Coin::getBalance($transaction['to']));
    }
}
