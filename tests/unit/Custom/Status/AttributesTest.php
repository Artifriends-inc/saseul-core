<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Constant\Role;
use Saseul\Custom\Status\Attributes;
use Saseul\System\Database;

class AttributesTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();

        $this->db->getAttributesCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getAttributesCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x6aaaa';

        $this->db->getAttributesCollection()->insertOne([
            'address' => '',
            'key' => 'role',
            'value' => Role::LIGHT,
        ]);

        Attributes::setRole($address, Role::VALIDATOR);

        // Act
        Attributes::_Save();

        // Assert
        $actual = $this->db->getAttributesCollection()->findOne(['address' => $address]);
        $this->assertSame(Role::VALIDATOR, $actual['value']);
    }
}
