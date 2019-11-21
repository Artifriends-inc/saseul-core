<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\Token;
use Saseul\System\Database;

class TokenTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->db->getTokenCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getTokenCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x60aaaa';
        $tokenName = 'Test10';

        $this->db->getTokenCollection()->insertOne([
            'address' => $address,
            'token_name' => $tokenName,
            'balance' => 1000,
        ]);

        Token::SetBalance($address, $tokenName, 1010);

        // Act
        Token::_save();

        // Assert
        $actual = $this->db->getTokenCollection()->findOne(['address' => $address, 'token_name' => $tokenName]);
        $this->assertSame(1010, $actual['balance']);
    }
}
