<?php

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;

class EnvTest extends TestCase
{
    public function testGivenGenesisKeyPathThenLoadGenesisKeyReturnKeyHash(): void
    {
        // Arrange
        $genesisKeyPath = './data/genesis_key.json';

        // Act
        $genesisKey = Env::loadGenesisKey($genesisKeyPath);

        // Assert
        static::assertIsArray($genesisKey);
        static::assertArrayHasKey('genesis_message', $genesisKey);
        static::assertArrayHasKey('special_thanks', $genesisKey);
    }

    public function testGivenHostInfoEnvThenLoad(): void
    {
        // Arrange
        $assertEnv = [
            'host' => '10.10.10.10',
            'address' => '0x6f00000',
            'public_key' => '11111111',
            'private_key' => '22222222',
        ];

        putenv("NODE_HOST={$assertEnv['host']}");
        putenv("NODE_ADDRESS={$assertEnv['address']}");
        putenv("NODE_PUBLIC_KEY={$assertEnv['public_key']}");
        putenv("NODE_PRIVATE_KEY={$assertEnv['private_key']}");

        // Act
        Env::load();

        // Assert
        static::assertSame($assertEnv['host'], Env::$nodeInfo['host']);
        static::assertSame($assertEnv['public_key'], Env::$nodeInfo['public_key']);
    }
}
