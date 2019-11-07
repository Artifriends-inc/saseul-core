<?php

namespace Saseul\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Directory;
use Saseul\Core\Generation;
use Saseul\Core\Property;
use Saseul\System\Database;

class GenerationTest extends TestCase
{
    protected static $archiveFile;
    private $db;

    public static function tearDownAfterClass(): void
    {
        unlink(self::$archiveFile);
    }

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->getGenerationsCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getGenerationsCollection()->drop();
    }

    public function testGivenGenerationFinalizeDataThenUpdate(): void
    {
        // Arrange
        $defaultGenerationData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'origin_block_number' => 1,
            'final_blockhash' => '',
            'final_block_number' => 2,
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];
        $this->db->getGenerationsCollection()->insertOne($defaultGenerationData);

        $insertFinalizeData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'final_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862905',
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];

        // Act
        Generation::update($insertFinalizeData);

        // Assert
        $actural = $this->db->getGenerationsCollection()->findOne(['origin_blockhash' => $insertFinalizeData['origin_blockhash']]);
        $this->assertSame($insertFinalizeData['origin_blockhash'], $actural['origin_blockhash']);
        $this->assertSame($insertFinalizeData['final_blockhash'], $actural['final_blockhash']);
    }

    public function testArchiveSource(): void
    {
        // Act
        Generation::archiveSource();

        // Assert
        $sourceHash = (new Property())->getSourceHash();
        self::$archiveFile = Directory::TAR_SOURCE_DIR . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";

        $this->assertTrue(is_file(self::$archiveFile));
    }
}
