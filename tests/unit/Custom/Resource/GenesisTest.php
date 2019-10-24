<?php

namespace Saseul\tests\Custom\Resource;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saseul\Common\AbstractResource;
use Saseul\Constant\Directory;
use Saseul\Constant\MongoDb;
use Saseul\Core\Chunk;
use Saseul\Custom\Resource\Genesis;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class GenesisTest extends TestCase
{
    private $manager;
    private $blockData;
    private $sut;
    private $sutName;
    private $timestamp;

    protected function setUp(): void
    {
        $this->manager = new Manager('mongodb://mongo');

        $this->blockData = [
            'block_number' => 1,
            'last_blockhash' => '',
            'blockhash' => 'de8d0c3b9d378118e5542dc8cb2090abbdc08348c895725b33c5a38ce772fa79',
            'transaction_count' => 2,
            's_timestamp' => 1562121069000000,
            'timestamp' => 1562121077974200,
        ];

        $this->sut = new Genesis();
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
        $this->timestamp = DateTime::Microtime();
    }

    protected function tearDown(): void
    {

    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractResource::class, $this->sut);
    }

    public function testGivenBlockDataThenValidityMethodReturnsFalse(): void
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

        $bulk = new BulkWrite();
        $bulk->insert($this->blockData);
        $this->manager->executeBulkWrite(MongoDb::NAMESPACE_BLOCK, $bulk);

        // Act
        $actual = $this->sut->getValidity();

        // Assert
        $this->assertFalse($actual);
    }

    public function testGivenBlockDataThenProcess(): void
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

        $this->assertArrayHasKey('transaction', $apiChunk[0]);
        $this->assertArrayHasKey('signature', $apiChunk[0]);
        $this->assertSame($signature, $apiChunk[0]['signature']);
        $this->assertSame($publicKey, $apiChunk[0]['public_key']);

        // Teardown
        unlink($apiChunkFile);
    }
}
