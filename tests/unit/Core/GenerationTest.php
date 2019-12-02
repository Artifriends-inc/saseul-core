<?php

namespace Saseul\Test\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Directory;
use Saseul\Core\Generation;
use Saseul\Core\Property;
use Saseul\System\Database;

class GenerationTest extends TestCase
{
    protected static $archiveFile;
    protected static $db;
    protected static $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getGenerationsCollection()->drop();

        self::$sut = new Generation();
    }

    public static function tearDownAfterClass(): void
    {
        unlink(self::$archiveFile);
    }

    protected function tearDown(): void
    {
        self::$db->getGenerationsCollection()->drop();
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
        self::$db->getGenerationsCollection()->insertOne($defaultGenerationData);

        $insertFinalizeData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'final_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862905',
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];

        // Act
        Generation::update($insertFinalizeData);

        // Assert
        $actural = self::$db->getGenerationsCollection()->findOne(['origin_blockhash' => $insertFinalizeData['origin_blockhash']]);
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

    public function testGivenGenerationDataThenGetCurrentGenerationData(): void
    {
        // Arrange
        $insertData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'origin_block_number' => 1,
            'final_blockhash' => '',
            'final_block_number' => 2,
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];
        self::$db->getGenerationsCollection()->insertOne($insertData);

        // Act
        $actual = Generation::current();

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('origin_blockhash', $actual);
        $this->assertSame(
            $insertData['origin_blockhash'],
            $actual['origin_blockhash']
        );
        $this->assertSame(
            $insertData['origin_block_number'],
            $actual['origin_block_number']
        );
        $this->assertSame(
            $insertData['source_hash'],
            $actual['source_hash']
        );
    }

    public function testGivenNoneGenerationDataThenGetCurrentGenerationData(): void
    {
        // Arrange
        $insertData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'origin_block_number' => 1,
            'final_blockhash' => '',
            'final_block_number' => 2,
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];

        Property::sourceHash($insertData['source_hash']);
        Property::sourceVersion($insertData['source_version']);

        // Act
        $actual = Generation::current();

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('source_hash', $actual);
        $this->assertSame(
            $insertData['source_hash'],
            $actual['source_hash']
        );
        $this->assertSame(
            $insertData['source_version'],
            $actual['source_version']
        );
    }

    public function testGivenOriginBlockNumberThenGetGenerationData(): void
    {
        // Arrange
        $insertData = [
            'origin_blockhash' => 'b8e68a44aef46a3bbb5768057edea067c108768872ec1fea7aa33583ea862903',
            'origin_block_number' => 1,
            'final_blockhash' => '',
            'final_block_number' => 2,
            'source_hash' => '4ddf5c617192c1b81ac9b0ab0eae3cfe43ab9f94f3a86a814767494147b8b05c',
            'source_version' => '1.0.0.3',
        ];
        self::$db->getGenerationsCollection()->insertOne($insertData);

        // Act
        $actual = Generation::generationByNumber($insertData['origin_block_number']);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('source_hash', $actual);
        $this->assertSame($insertData['source_hash'], $actual['source_hash']);
        $this->assertSame(
            $insertData['origin_block_number'],
            $actual['origin_block_number']
        );
    }
}
