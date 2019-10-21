<?php

namespace Saseul\tests\Custom\Resource;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saseul\Common\AbstractResource;
use Saseul\Constant\Directory;
use Saseul\Core\Chunk;
use Saseul\Custom\Resource\Deposit;
use Saseul\System\Key;

class DepositTest extends TestCase
{
    private $sut;
    private $sutName;
    private $timestamp;

    protected function setUp(): void
    {
        $this->sut = new Deposit();
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
        $this->timestamp = \Saseul\Util\DateTime::Microtime();
    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        static::assertInstanceOf(AbstractResource::class, $this->sut);
    }

    public function testGivenBlockDataThenProcessCheck(): void
    {
        // Arrange
        $request = [
            'type' => $this->sutName,
            'from' => '0x6f1b0f1ae759165a92d2e7d0b4cae328a1403aa5e35a85',
            'timestamp' => $this->timestamp
        ];

        $thash = hash('sha256', json_encode($request));
        $privateKey = 'a745fbb3860f243293a66a5fcadf70efc1fa5fa5f0254b3100057e753ef0d9bb';
        $publicKey = '52017bcb4caca8911b3830c281d10f79359ceb3fbe061c990e043ccb589fccc3';
        $signature = Key::makeSignature($thash, $privateKey, $publicKey);
        $this->sut->initialize($request, $thash, $publicKey, $signature);

        // Act
        $this->sut->process();

        // Assert
        $apiChunkId = Chunk::getId($this->timestamp);
        $apiChunkFile = Directory::API_CHUNKS . '/' . $apiChunkId . '.json';
        $apiChunk = Chunk::getChunk($apiChunkFile);

        static::assertArrayHasKey('transaction', $apiChunk[0]);
        static::assertArrayHasKey('signature', $apiChunk[0]);
        static::assertSame($signature, $apiChunk[0]['signature']);
        static::assertSame($publicKey, $apiChunk[0]['public_key']);

        // Teardown
        unlink($apiChunkFile);
    }
}
