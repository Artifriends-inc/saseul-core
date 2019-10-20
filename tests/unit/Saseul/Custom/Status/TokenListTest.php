<?php

namespace Saseul\Tests\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Status\TokenList;
use Saseul\System\Database;

class TokenListTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();

        $this->db->getTokenListCollection()->drop();
    }

    protected function tearDown(): void
    {
        $this->db->getTokenListCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $tokenName = 'Test10';

        $this->db->getTokenListCollection()->insertOne([
            'token_name' => $tokenName,
            'info' => '',
        ]);

        TokenList::SetInfo($tokenName, 'test');

        // Act
        TokenList::_Save();

        // Assert
        $actual = $this->db->getTokenListCollection()->findOne(['token_name' => $tokenName]);
        $this->assertSame('test', $actual['info']);
    }
}
