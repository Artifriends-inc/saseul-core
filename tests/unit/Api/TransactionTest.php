<?php

namespace Saseul\tests\Api;

use PHPUnit\Framework\TestCase;
use Saseul\Api\Transaction;
use Saseul\Common\ExternalApi;
use Saseul\Constant\Account;
use Saseul\Core\Env;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class TransactionTest extends TestCase
{
    public function testSutInheritsExternalApi(): void
    {
        // Arrange
        $sut = new Transaction();

        // Assert
        $this->assertInstanceOf(ExternalApi::class, $sut);
    }

    public function testValidTransactionReturnsOK(): void
    {
        // Arrange
        $this->prepareTransaction('SendCoin');
        $sut = new Transaction();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::OK, $actual->getCode());
        $this->assertIsArray($actual->getData());
        $this->assertTrue(array_key_exists('message', $actual->getData()));
        $this->assertTrue(array_key_exists('transaction', $actual->getData()));
        $this->assertTrue(array_key_exists('public_key', $actual->getData()));
        $this->assertTrue(array_key_exists('signature', $actual->getData()));
    }

    public function testGivenNonExistentTypeThenReturnsNotFound(): void
    {
        // Arrange
        $this->prepareTransaction('ArtiCoin');
        $sut = new Transaction();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::NOT_FOUND, $actual->getCode());
    }

    public function testGivenInvalidPublicKeyThenReturnsBadRequest(): void
    {
        // Arrange
        $this->prepareTransaction('SendCoin');
        $invalidPublicKey = '0x000101010';
        $_REQUEST['public_key'] = $invalidPublicKey;
        $sut = new Transaction();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::BAD_REQUEST, $actual->getCode());
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
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::BAD_REQUEST, $actual->getCode());
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

        $_SERVER['REQUEST_URI'] = '/transaction';

        $this->prepareNodeInfo();
    }

    private function prepareNodeInfo(): void
    {
        Env::$nodeInfo['host'] = 'host';
        Env::$nodeInfo['public_key'] = 'public_key';
        Env::$nodeInfo['private_key'] = 'private_key';
        Env::$nodeInfo['address'] = 'address';
    }
}
