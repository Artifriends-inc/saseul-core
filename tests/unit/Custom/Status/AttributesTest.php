<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Role;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Status\Attributes;
use Saseul\System\Database;

class AttributesTest extends TestCase
{
    protected static $db;
    protected static $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getAttributesCollection()->drop();

        self::$sut = new Attributes();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getAttributesCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getAttributesCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x6aaaa';

        self::$db->getAttributesCollection()->insertOne([
            'address' => '',
            'key' => 'role',
            'value' => Role::LIGHT,
        ]);

        Attributes::setRole($address, Role::VALIDATOR);

        // Act
        Attributes::_save();

        // Assert
        $actual = self::$db->getAttributesCollection()->findOne(['address' => $address]);
        $this->assertSame(Role::VALIDATOR, $actual['value']);
    }

    public function testGivenRoleDataThenLoad(): void
    {
        // Arrange
        Attributes::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'key' => 'role',
                'value' => Role::VALIDATOR
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'key' => 'role',
                'value' => Role::LIGHT
            ]
        ];

        self::$db->getAttributesCollection()->insertMany($saveData);

        Attributes::loadRole($address);

        // Act
        Attributes::_load();

        // Assert
        $allAddress = self::$sut->getAllAddressList();
        $allRole = self::$sut->getAllRoleList();

        $this->assertIsArray($allAddress);
        $this->assertIsArray($allRole);

        $this->assertArrayHasKey($address, $allRole);
        $this->assertSame($saveData[0]['value'], $allRole[$address]);
    }

    public function testGivenEmptyDataThenLoadReturnEmpty(): void
    {
        // Arrange
        Attributes::_reset();

        // Act
        Attributes::_load();

        // Assert
        $this->assertEmpty(self::$sut->getAllAddressList());
        $this->assertEmpty(self::$sut->getAllRoleList());
    }
}
