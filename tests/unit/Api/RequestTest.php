<?php

namespace Saseul\tests\Api;

use PHPUnit\Framework\TestCase;
use Saseul\Api\Request;
use Saseul\Common\ExternalApi;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class RequestTest extends TestCase
{
    public function testSutInheritsExternalApi(): void
    {
        // Arrange
        $sut = new Request();

        // Assert
        static::assertInstanceOf(ExternalApi::class, $sut);
    }

    public function testValidRequestReturnsOK(): void
    {
        // Arrange
        $this->prepareRequest('GetBalance');
        $sut = new Request();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        static::assertNotNull($actual);
        static::assertInstanceOf(HttpResponse::class, $actual);
        static::assertSame(HttpStatus::OK, $actual->getCode());
        static::assertIsArray($actual->getData());
        static::assertTrue(array_key_exists('balance', $actual->getData()));
        static::assertTrue(array_key_exists('deposit', $actual->getData()));
    }

    public function testGivenNonExistentTypeThenReturnsNotFound(): void
    {
        // Arrange
        $this->prepareRequest('ArtiFriends');
        $sut = new Request();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        static::assertNotNull($actual);
        static::assertInstanceOf(HttpResponse::class, $actual);
        static::assertSame(HttpStatus::NOT_FOUND, $actual->getCode());
    }

    public function testGivenInvalidPublicKeyThenReturnsBadRequest(): void
    {
        // Arrange
        $this->prepareRequest('GetBalance');
        $invalidPublicKey = '0x0009999';
        $_REQUEST['public_key'] = $invalidPublicKey;
        $sut = new Request();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        static::assertNotNull($actual);
        static::assertInstanceOf(HttpResponse::class, $actual);
        static::assertSame(HttpStatus::BAD_REQUEST, $actual->getCode());
    }

    public function testGivenInvalidSignatureThenRaisesException(): void
    {
        // Arrange
        $this->prepareRequest('GetBalance');
        $invalidSignature =
            'a68a4dcdd3eb8fcf5648ca1eb913b28a74ad8e21607fb7ec8605635eeb9b83e669'
            . '0b9838a698b37107195f2337f9d46ff5827adfb2de81a2b83e6d6c89f93305';
        $_REQUEST['signature'] = $invalidSignature;
        $sut = new Request();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        static::assertNotNull($actual);
        static::assertInstanceOf(HttpResponse::class, $actual);
        static::assertSame(HttpStatus::BAD_REQUEST, $actual->getCode());
    }

    private function prepareRequest($type): void
    {
        $request = [
            'type' => $type,
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'version' => '1.0',
            'timestamp' => DateTime::Date()
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $public_key = '2704e240b3e806f476211694c2bda537bcf56941199f4447468021f9c3833a33';
        $signature = Key::makeSignature($thash, $private_key, $public_key);

        $_REQUEST = [
            'request' => json_encode($request),
            'public_key' => $public_key,
            'signature' => $signature
        ];

        $this->prepareHandler();
    }

    private function prepareHandler(): void
    {
        $_SERVER['REQUEST_URI'] = '/request';
    }
}
