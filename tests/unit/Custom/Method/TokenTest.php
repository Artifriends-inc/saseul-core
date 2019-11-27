<?php

namespace Saseul\Test\Unit\Custom\Method;

use PHPUnit\Framework\TestCase;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Method\Token;
use Saseul\System\Database;

class TokenTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getTokenCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTokenCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getTokenCollection()->drop();
    }

    public function testGivenSingleUserDataThenGetTokenInfo(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'token_name' => 'Token-11',
                'balance' => 1000000,
            ],
            [
                'address' => $address,
                'token_name' => 'Token-12',
                'balance' => 20000,
            ]
        ];
        self::$db->getTokenCollection()->insertMany($saveData);

        // Act
        $actual = Token::getAll([$address]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);

        $tokenInfo = $this->parseTokenInfo($actual[$address]);

        $this->assertArrayHasKey($saveData[0]['token_name'], $tokenInfo);
        $this->assertSame($saveData[1]['balance'], $tokenInfo[$saveData[1]['token_name']]);
    }

    public function testGivenMultiUserDataThenGetTokenInfo(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'token_name' => 'Token-11',
                'balance' => 1004,
            ],
            [
                'address' => '0x6fa9fd4cf2a675b0ca54fb99ce8b1d14600178053dc2f2',
                'token_name' => 'Token-11',
                'balance' => 1000,
            ],
        ];
        self::$db->getTokenCollection()->insertMany($saveData);

        // Act
        $actual = Token::getAll([$address, $saveData[1]['address']]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);
        $this->assertArrayHasKey($saveData[1]['address'], $actual);

        $firstUserTokenInfo = $this->parseTokenInfo($actual[$address]);
        $secondUserTokenInfo = $this->parseTokenInfo($actual[$saveData[1]['address']]);

        $this->assertArrayHasKey($saveData[0]['token_name'], $firstUserTokenInfo);
        $this->assertSame($saveData[0]['balance'], $firstUserTokenInfo[$saveData[0]['token_name']]);

        $this->assertArrayHasKey($saveData[1]['token_name'], $secondUserTokenInfo);
    }

    public function testGivenSingleUserMultiTokenDataThenGetTokenInfoReturnOneTokenInfo(): void
    {
        // Arrange
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'address' => $address,
                'token_name' => 'Token-11',
                'balance' => 1000000,
            ],
            [
                'address' => $address,
                'token_name' => 'Token-12',
                'balance' => 20000,
            ]
        ];
        self::$db->getTokenCollection()->insertMany($saveData);

        // Act
        $actual = Token::getAll([$address], [$saveData[0]['token_name']]);

        // Assert
        $this->assertIsArray($actual);
        $this->assertArrayHasKey($address, $actual);

        $userTokenInfo = $this->parseTokenInfo($actual[$address]);
        $this->assertArrayHasKey($saveData[0]['token_name'], $userTokenInfo);
        $this->assertArrayNotHasKey($saveData[1]['token_name'], $userTokenInfo);
    }

    private function parseTokenInfo(array $tokenInfo): array
    {
        $result = [];
        foreach ($tokenInfo as $item) {
            $result[$item['name']] = $item['balance'];
        }

        return $result;
    }
}
