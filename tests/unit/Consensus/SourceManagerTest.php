<?php

namespace Saseul\Tests\Unit\Consensus;

use PHPUnit\Framework\TestCase;
use Saseul\Consensus\SourceManager;
use Saseul\Constant\Directory;
use Saseul\Core\Generation;
use Saseul\Core\Property;

class SourceManagerTest extends TestCase
{
    protected static $sourceFullPath;
    protected static $archiveFile;

    public static function tearDownAfterClass(): void
    {
        shell_exec('rm -rf ' . static::$sourceFullPath);
        unlink(self::$archiveFile);
    }

    public function testGivenSourceArchiveDataThenRestoreSource(): void
    {
        // Arrange
        Generation::archiveSource();

        $sourceHash = (new Property())->getSourceHash();
        self::$archiveFile = Directory::TAR_SOURCE_DIR . '/' . Directory::SOURCE_PREFIX . "{$sourceHash}.tar.gz";

        // Act
        $actual = (new SourceManager())->restoreSource(self::$archiveFile, $sourceHash);
        self::$sourceFullPath = $actual;

        // Assert
        $this->assertIsString($actual);
        $this->assertDirectoryExists($actual);
    }
}
