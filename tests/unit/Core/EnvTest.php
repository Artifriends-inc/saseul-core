<?php

namespace Saseul\Test\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Env;

class EnvTest extends TestCase
{
    public function testGivenGenesisKeyPathThenLoadGenesisKeyReturnKeyHash(): void
    {
        // Arrange
        $genesisKeyPath = './data/core/genesis_key.json';

        // Act
        $genesisKey = Env::loadGenesisKey($genesisKeyPath);

        // Assert
        $this->assertIsArray($genesisKey);
        $this->assertArrayHasKey('genesis_message', $genesisKey);
        $this->assertArrayHasKey('special_thanks', $genesisKey);
    }

    public function testGivenGenesisInfoEnvThenLoad(): void
    {
        // Arrange
        $assertGenesisEnv = [
            'host' => '10.10.10.10',
            'address' => '0x.565885',
            'coin_amount' => '1000',
            'deposit_amount' => '100',
            'key_path' => './data/core/genesis_key.json',
        ];

        putenv("GENESIS_HOST={$assertGenesisEnv['host']}");
        putenv("GENESIS_ADDRESS={$assertGenesisEnv['address']}");
        putenv("GENESIS_COIN_VALUE={$assertGenesisEnv['coin_amount']}");
        putenv("GENESIS_DEPOSIT_VALUE={$assertGenesisEnv['deposit_amount']}");
        putenv("GENESIS_KEY_PATH={$assertGenesisEnv['key_path']}");

        // Act
        Env::load();

        // Assert
        $this->assertSame($assertGenesisEnv['host'], Env::$genesis['host']);
        $this->assertSame($assertGenesisEnv['address'], Env::$genesis['address']);
        $this->assertSame($assertGenesisEnv['coin_amount'], Env::$genesis['coin_amount']);
        $this->assertSame($assertGenesisEnv['deposit_amount'], Env::$genesis['deposit_amount']);

        $genesisKey = ENV::loadGenesisKey($assertGenesisEnv['key_path']);
        $this->assertIsArray(ENV::$genesis['key']);
        $this->assertSame($genesisKey, Env::$genesis['key']);
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
        $this->assertSame($assertEnv['host'], Env::$nodeInfo['host']);
        $this->assertSame($assertEnv['public_key'], Env::$nodeInfo['public_key']);
    }
}
