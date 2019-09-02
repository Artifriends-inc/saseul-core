<?php

namespace Saseul\tests\Api;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Saseul\Api\Transaction;
use Saseul\Constant\Account;
use Saseul\Core\Env;
use Saseul\System\Key;
use Saseul\System\Terminator;
use Saseul\Util\DateTime;

class TransactionTest extends TestCase
{
    public function setUp(): void
    {
        Terminator::setTestMode();
        $_REQUEST = [];
    }

    public function testGivenValidTransactionThen_ProcessDoesNotRaiseException(): void
    {
        // Arrange
        $this->prepareTransaction('SendCoin');
        $sut = new Transaction();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNull($actual);
    }

    public function testGivenInvalidTypeThenRaisesException(): void
    {
        // Arrange
        $this->prepareTransaction('SendKakaoMoney');
        $sut = new Transaction();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(Exception::class, $actual);
        $this->assertEquals('fail', $actual->getMessage());
    }

    public function testGivenPublicKeyThenRaisesException(): void
    {
        // Arrange
        $this->prepareTransaction('SendCoin');
        $invalidPublicKey = '0x0009999';
        $_REQUEST['public_key'] = $invalidPublicKey;
        $sut = new Transaction();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(Exception::class, $actual);
        $this->assertEquals('fail', $actual->getMessage());
    }

    public function testGivenInvalidSignatureThenRaisesException(): void
    {
        // Arrange
        $this->prepareTransaction('SendCoin');
        $invalidSignature =
            'a68a4dcdd3eb8fcf5648ca1eb913b28a74ad8e21607fb7ec8605635eeb9b83e669'
            .'0b9838a698b37107195f2337f9d46ff5827adfb2de81a2b83e6d6c89f93305';
        $_REQUEST['signature'] = $invalidSignature;
        $sut = new Transaction();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(Exception::class, $actual);
        $this->assertEquals('fail', $actual->getMessage());
    }

    private function prepareTransaction($type): void
    {
        $amount = 1000;
        Env::$genesis['coin_amount'] = $amount + 100;
        $transaction = [
            'type' => $type,
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'version' => '1.0',
            'timestamp' => DateTime::Date(),
            'to' => str_repeat('*', Account::ADDRESS_SIZE),
            'amount' => $amount,
            'fee' => 100
        ];

        $thash = hash('sha256', json_encode($transaction));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $public_key = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $signature = Key::makeSignature($thash, $private_key, $public_key);

        $_REQUEST = [
            'transaction' => json_encode($transaction),
            'public_key' => $public_key,
            'signature' => $signature
        ];
    }

    private function methodInvoker($object, $method)
    {
        try
        {
            $invoker = new ReflectionMethod($object, $method);
            $invoker->invoke($object);
            return null;
        }
        catch (Exception $exception)
        {
            return $exception;
        }
    }
}
