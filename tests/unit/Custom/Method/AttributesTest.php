<?php

namespace Saseul\Test\Unit\Custom\Method;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Role;
use Saseul\Custom\Method\Attributes;
use Saseul\System\Database;

class AttributesTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getAttributesCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getAttributesCollection()->drop();
    }

    protected function setUp(): void
    {
        $insertData = [
            [
                'address' => '0x0001',
                'key' => 'role',
                'value' => Role::VALIDATOR,
            ],
            [
                'address' => '0x0002',
                'key' => 'role',
                'value' => Role::ARBITER,
            ],
            [
                'address' => '0x0003',
                'key' => 'role',
                'value' => Role::SUPERVISOR,
            ],
            [
                'address' => '0x0004',
                'key' => 'role',
                'value' => Role::LIGHT,
            ]
        ];
        self::$db->getAttributesCollection()->insertMany($insertData);
    }

    protected function tearDown(): void
    {
        self::$db->getAttributesCollection()->drop();
    }

    public function testGetFullNodeData(): void
    {
        // Act
        $actual = Attributes::getFullNode();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x0001', $actual);
        $this->assertNotContains('0x0004', $actual);
    }

    public function testGetValidatorData(): void
    {
        // Act
        $actual = Attributes::getValidator();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x0001', $actual);
        $this->assertNotContains('0x0002', $actual);
        $this->assertNotContains('0x0004', $actual);
    }

    public function testGetSupervisorData(): void
    {
        // Act
        $actual = Attributes::getSupervisor();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x0003', $actual);
        $this->assertNotContains('0x0001', $actual);
        $this->assertNotContains('0x0004', $actual);
    }

    public function testGetArbiterData(): void
    {
        // Act
        $actual = Attributes::getArbiter();

        // Assert
        $this->assertIsArray($actual);
        $this->assertContains('0x0002', $actual);
        $this->assertNotContains('0x0001', $actual);
        $this->assertNotContains('0x0004', $actual);
    }
}
