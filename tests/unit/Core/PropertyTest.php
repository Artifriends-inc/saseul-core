<?php

namespace Saseul\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Saseul\Core\Property;
use Saseul\System\Cache;

class PropertyTest extends TestCase
{
    protected function setUp(): void
    {
        $this->initCache();
    }

    protected function tearDown(): void
    {
        $this->initCache();
    }

    public function testGivenSourceHashDataThenGetSourceHash(): void
    {
        // Arrange
        Property::sourceHash('datadidkdid');

        // Act
        $actual = (new Property())->getSourceHash();

        // Assert
        $this->assertIsString($actual);
        $this->assertSame('datadidkdid', $actual);
    }

    private function initCache(): void
    {
        $keyArray = [
            'isReady' => true,
            'isRoundRunning' => false,
            'iMLog' => [],
            'aliveNode' => [],
            'excludeHost' => [],
            'subjectNode' => [],
            'registerRequest' => [],
            'sourceHash' => '',
            'sourceVersion' => '',
        ];

        foreach ($keyArray as $key => $value) {
            Cache::GetInstance()->set("p_{$key}", $value);
        }
    }
}
