<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Status\Token;
use Saseul\System\Database;

class TokenTest extends TestCase
{
    protected static $db;
    protected static $sut;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getTokenCollection()->drop();

        self::$sut = new Token();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTokenCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getTokenCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $address = '0x60aaaa';
        $tokenName = 'Test10';

        self::$db->getTokenCollection()->insertOne([
            'address' => $address,
            'token_name' => $tokenName,
            'balance' => 1000,
        ]);

        Token::setBalance($address, $tokenName, 1010);

        // Act
        Token::_save();

        // Assert
        $actual = self::$db->getTokenCollection()->findOne(['address' => $address, 'token_name' => $tokenName]);
        $this->assertSame(1010, $actual['balance']);
    }

    public function testGivenTokenDataThenLoad(): void
    {
        // Arrange
        Token::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'token_name' => 'Token-11',
                'balance' => 100000,
            ],
            [
                'address' => $address,
                'token_name' => 'Token-12',
                'balance' => 200000,
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'token_name' => 'Token-12',
                'balance' => 4000000
            ]
        ];
        self::$db->getTokenCollection()->insertMany($saveData);

        Token::loadToken($address, $saveData[0]['token_name']);
        Token::loadToken($address, $saveData[1]['token_name']);
        Token::loadToken($saveData[2]['address'], $saveData[2]['token_name']);

        // Act
        Token::_load();

        // Assert
        $allAddress = self::$sut->getAllAddressList();
        $allTokenName = self::$sut->getAllTokenNameList();
        $allTokenBalance = self::$sut->getAllTokenBalance();

        $this->assertIsArray($allAddress);
        $this->assertIsArray($allTokenName);
        $this->assertIsArray($allTokenBalance);

        $this->assertContains($address, $allAddress);
        $this->assertContains($saveData[0]['token_name'], $allTokenName);
        $this->assertContains($saveData[0]['balance'], $allTokenBalance[$address]);
        $this->assertArrayHasKey($saveData[2]['token_name'], $allTokenBalance[$saveData[2]['address']]);
    }

    public function testGivenNoneAddressDataThenLoad(): void
    {
        // Arrange
        Token::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'token_name' => 'Token-11',
                'balance' => 100000,
            ],
            [
                'address' => $address,
                'token_name' => 'Token-12',
                'balance' => 200000,
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'token_name' => 'Token-12',
                'balance' => 4000000
            ]
        ];
        self::$db->getTokenCollection()->insertMany($saveData);

        // Act
        Token::_load();

        // Assert
        $allAddress = self::$sut->getAllAddressList();
        $allTokenName = self::$sut->getAllTokenNameList();
        $allTokenBalance = self::$sut->getAllTokenBalance();

        $this->assertEmpty($allAddress);
        $this->assertEmpty($allTokenName);
        $this->assertEmpty($allTokenBalance);
    }
}
