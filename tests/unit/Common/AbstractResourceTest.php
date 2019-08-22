<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractResource;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class AbstractResourceTest extends TestCase
{
    private $publicKey;
    private $sut;
    private $sutName;
    private $timestamp;
    private $address;

    protected function setUp(): void
    {
        $this->sut = $this->getMockForAbstractClass(AbstractResource::class);
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
        $this->timestamp = DateTime::Microtime();

        $this->publicKey = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $this->address = '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85';
    }

    public function testGivenInvalidFromAddressThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $request = [
            'type' => $this->sutName,
            'from' => '0x000000000000000000000000000000000000000000000',
            'timestamp' => $this->timestamp
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee00000169dca49c4d';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $this->sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenInvalidSignatureThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $request = [
            'type' => $this->sutName,
            'from' => $this->address,
            'timestamp' => $this->timestamp
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $this->sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenNotSamePublicKeyThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $request = [
            'type' => $this->sutName,
            'from' => $this->address,
            'timestamp' => $this->timestamp
        ];

        $thash = hash('sha256', json_encode($request));
        $private_key = 'a609aca90f9338da02e640c7df8ae760211bef48031973ee12345169dca49cff';
        $public_key = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fcc44';
        $signature = Key::makeSignature($thash, $private_key, $public_key);
        $this->sut->initialize($request, $thash, $public_key, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }
}
