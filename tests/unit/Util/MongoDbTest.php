<?php


use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use Saseul\Util\MongoDb;

class MongoDbTest extends TestCase
{
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new Manager('mongodb://mongo');
    }

    public function testGivenInvalidMongoDBThenExecuteBulkCommandReturnFalse(): void
    {
        // Arrange
        $this->manager = new Manager('mongodb://mango');
        $command = new Command(['create' => 'test']);

        // Act
        $actual = (new MongoDb)->executeBulkCommand('test', [$command]);

        // Assert
        $this->assertFalse($actual);
    }
}
