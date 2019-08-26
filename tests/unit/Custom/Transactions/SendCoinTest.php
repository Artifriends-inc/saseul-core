<?php

namespace Saseul\Common;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Account;
use Saseul\Core\Env;
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

    public function setUp(): void
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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertTrue($actual);
    }

    /**
     * 이 테스트는 기존의 _Init, _GetValidity 2개의 메서드가 새로 추가된
     * initialize 와 getValidity 메서드를 정상적으로 호출하는지 확인하기 위한 테스트로
     * Transaction API의 코드가 다 정리되어 모든 Transaction Api가
     * AbstractTransaction을 모두 구현할 때 제거되어야 하는 테스트다.
     */
    public function test_GetValidityMethodReturnsTrue(): void
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
            $thash, $this->privateKey, $this->publicKey);
        $this->sut->_Init($transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->_GetValidity();

        // Assert
        $this->assertTrue($actual);
    }

    public function test_LoadStatusCallsLoadStatusMethod(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(SendCoin::class)
            ->onlyMethods(['loadStatus'])
            ->getMock();

        // Acts
        $sut->expects($this->once())
            ->method('loadStatus');

        // Assert
        $sut->_LoadStatus();
    }

    public function test_MakeDecisionMehtodCallsMakeDecision(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(SendCoin::class)
            ->onlyMethods(['makeDecision'])
            ->getMock();

        // Acts
        $sut->expects($this->once())->method('makeDecision');

        // Assert
        $sut->_MakeDecision();
    }

    public function test_GetStatusMehtodCallsGetStatus(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(SendCoin::class)
            ->onlyMethods(['getStatus'])
            ->getMock();

        // Acts
        $sut->expects($this->once())->method('getStatus');

        // Assert
        $sut->_GetStatus();
    }

    public function test_SetStatusMehtodCallsSetStatus(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(SendCoin::class)
            ->onlyMethods(['setStatus'])
            ->getMock();

        // Acts
        $sut->expects($this->once())->method('setStatus');

        // Assert
        $sut->_SetStatus();
    }
}
