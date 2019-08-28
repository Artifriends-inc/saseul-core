<?php

namespace Saseul\Custom\Transaction;

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractTransaction;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class ChangeRoleTest extends TestCase
{
    private $sut;
    private $sutName;
    private $from;
    private $publicKey;
    private $privateKey;
    private $version;
    private $timeStamp;

    public function setUp(): void
    {
        $this->sut = new ChangeRole();
        $this->sutName = 'ChangeRole';
        $this->from = '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20';
        $this->publicKey = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $this->privateKey = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $this->version = '1.0';
        $this->timeStamp = DateTime::Microtime();
    }
    public function testSutInheritsAbstractTransaction(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractTransaction::class, $this->sut);
    }

    public function testGivenNullRoleThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNonStringRoleThenGetValidityReturnsFalse(): void
    {
        // Arrange
        $transaction = [
            'type' => $this->sutName,
            'from' => $this->from,
            'version' => $this->version,
            'timestamp' => $this->timeStamp,
            'role' => 112
        ];
        $thash = $this->makeHash($transaction);
        $signature = $this->makeSignature($thash);
        $this->sut->initialize(
            $transaction, $thash, $this->publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
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
            'role' => 'role'
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
        $sut = $this->getMockBuilder(ChangeRole::class)
            ->onlyMethods(['loadStatus'])
            ->getMock();

        // Act
        $sut->expects($this->once())
            ->method('loadStatus');

        // Assert
        $sut->_LoadStatus();
    }

    public function test_GetStatusCallsGetStatusMethod(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(ChangeRole::class)
            ->onlyMethods(['getStatus'])
            ->getMock();

        // Act
        $sut->expects($this->once())
            ->method('getStatus');

        // Assert
        $sut->_GetStatus();
    }

    public function test_MakeDecisionCallsMakeDecisionMethod(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(ChangeRole::class)
            ->onlyMethods(['makeDecision'])
            ->getMock();

        // Act
        $sut->expects($this->once())
            ->method('makeDecision');

        // Assert
        $sut->_MakeDecision();
    }

    public function test_SetStatusCallsSetStatusMethod(): void
    {
        // Arrange
        $sut = $this->getMockBuilder(ChangeRole::class)
            ->onlyMethods(['setStatus'])
            ->getMock();

        // Act
        $sut->expects($this->once())
            ->method('setStatus');

        // Assert
        $sut->_SetStatus();
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
