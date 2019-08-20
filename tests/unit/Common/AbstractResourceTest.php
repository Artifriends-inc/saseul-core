<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractResource;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class AbstractResourceTest extends TestCase
{
    private $publicKey;

    protected function setUp(): void
    {
        $this->publicKey = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
    }

    public function testGivenInvalidFromThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $sut = $this->getMockForAbstractClass(AbstractResource::class);
        $sutName = (new ReflectionClass(get_class($sut)))->getShortName();
        $request = [
            'type' => $sutName,
            'from' => '0x000000000000000000000000000000000000000000000',
            'timestamp' => DateTime::Date()
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenInvalidSignatureThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $sut = $this->getMockForAbstractClass(AbstractResource::class);
        $sutName = (new ReflectionClass(get_class($sut)))->getShortName();
        $request = [
            'type' => $sutName,
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'timestamp' => DateTime::Date()
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNotSamePublicKeyThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $sut = $this->getMockForAbstractClass(AbstractResource::class);
        $sutName = (new ReflectionClass(get_class($sut)))->getShortName();
        $request = [
            'type' => $sutName,
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'timestamp' => DateTime::Date()
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fcc44';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }
}
