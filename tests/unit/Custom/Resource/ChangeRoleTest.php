<?php

namespace Saseul\Test\Unit\Custom\Resource;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Saseul\Common\AbstractResource;
use Saseul\Constant\Directory;
use Saseul\Core\Chunk;
use Saseul\Core\Env;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Resource\ChangeRole;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class ChangeRoleTest extends TestCase
{
    private $sut;
    private $sutName;
    private $timestamp;

    protected function setUp(): void
    {
        $this->sut = new ChangeRole();
        $this->sutName = (new ReflectionClass(get_class($this->sut)))->getShortName();
        $this->timestamp = DateTime::Microtime();
    }

    public function testSutInheritsAbstractRequest(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractResource::class, $this->sut);
    }

    public function testGivenChangeRoleDataThenProcessCheck(): void
    {
        // Arrange
        Env::load();

        $request = [
            'type' => $this->sutName,
            'from' => NodeInfo::getAddress(),
            'timestamp' => $this->timestamp,
        ];

        $thash = hash('sha256', json_encode($request, JSON_THROW_ON_ERROR, 512));
        $signature = Key::makeSignature($thash, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());
        $this->sut->initialize($request, $thash, NodeInfo::getPublicKey(), $signature);

        // Act
        $this->sut->process();

        // Assert
        $apiChunkId = Chunk::getId($this->timestamp);
        $apiChunkFile = Directory::API_CHUNKS . '/' . $apiChunkId . '.json';
        $apiChunk = Chunk::getChunk($apiChunkFile);

        $this->assertArrayHasKey('transaction', $apiChunk[0]);
        $this->assertArrayHasKey('signature', $apiChunk[0]);
        $this->assertSame($signature, $apiChunk[0]['signature']);
        $this->assertSame(NodeInfo::getPublicKey(), $apiChunk[0]['public_key']);

        // Teardown
        unlink($apiChunkFile);
    }
}
