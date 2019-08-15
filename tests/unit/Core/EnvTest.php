<?php


use Saseul\Core\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    public function testGivenGenesisKeyPathThenLoadGenesisKeyReturnKeyHash(): void
    {
        // Arrange
        $genesisKeyPath = './data/genesis_key.json';

        // Act
        Env::loadGenesisKey($genesisKeyPath);

        $genesisKey = Env::$genesis['key'];

        // Assert
        $this->assertIsArray($genesisKey);
        $this->assertArrayHasKey('genesis_message', $genesisKey);
        $this->assertArrayHasKey('special_thanks', $genesisKey);
    }
}
