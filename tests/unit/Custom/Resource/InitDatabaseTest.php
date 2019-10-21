<?php

namespace Saseul\tests\Custom\Resource;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saseul\Common\AbstractResource;
use Saseul\Custom\Resource\InitDatabase;
use Saseul\System\Key;

class InitDatabaseTest extends TestCase
{
    private $sut;
    private $sutName;

    protected function setUp(): void
    {
        $this->sut = new InitDatabase();
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        static::assertInstanceOf(AbstractResource::class, $this->sut);
    }

    public function testGivenInvalidFromAddressThenGetValidityMethodReturnsFalse(): void
    {
        // Arrange
        $request = [
            'type' => $this->sutName,
            'from' => '0x6f258c97ad7848aef661465018dc48e55131eff91c4e20',
            'timestamp' => \Saseul\Util\DateTime::Microtime()
        ];

        $thash = hash('sha256', json_encode($request));
        $privateKey = 'a745fbb3860f243293a66a5fcadf70efc1fa5fa5f0254b3100057e753ef0d9bb';
        $publicKey = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $privateKey, $publicKey);

        $this->sut->initialize($request, $thash, $publicKey, $signature);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        static::assertFalse($actual);
    }
}
