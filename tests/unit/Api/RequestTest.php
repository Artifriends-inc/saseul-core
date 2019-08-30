<?php

namespace Saseul\tests\Api;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Saseul\Api\Request;
use Saseul\System\Key;
use Saseul\System\Terminator;
use Saseul\Util\DateTime;

// TODO: Terminator를 사용하지 않고 테스트할 수 있는 방법 찾기
class RequestTest extends TestCase
{
    public function setUp(): void
    {
        Terminator::setTestMode();
        $_REQUEST = [];
    }

    public function testValidRequestDoesNotRaiseException(): void
    {
        // Arrange
        $this->prepareRequest('GetTransaction');
        $sut = new Request();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNull($actual);
    }

    public function testGivenInvalidTypeThenRaisesException(): void
    {
        // Arrange
        $this->prepareRequest('ArtiTransaction');
        $sut = new Request();
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
        $this->prepareRequest('GetTransaction');
        $invalidPublicKey = '0x0009999';
        $_REQUEST['public_key'] = $invalidPublicKey;
        $sut = new Request();
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
        $this->prepareRequest('GetTransaction');
        $invalidSignature =
            'a68a4dcdd3eb8fcf5648ca1eb913b28a74ad8e21607fb7ec8605635eeb9b83e669'
            .'0b9838a698b37107195f2337f9d46ff5827adfb2de81a2b83e6d6c89f93305';
        $_REQUEST['signature'] = $invalidSignature;

        $sut = new Request();
        $sut->_init();

        // Act
        $actual = $this->methodInvoker($sut, '_process');

        // Assert
        $this->assertNotNull($actual);
        $this->assertInstanceOf(Exception::class, $actual);
        $this->assertEquals('fail', $actual->getMessage());
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
