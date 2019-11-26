<?php

namespace Saseul\Test\Unit\Custom\Status;

use PHPUnit\Framework\TestCase;
use Saseul\Core\NodeInfo;
use Saseul\Custom\Status\TokenList;
use Saseul\System\Database;

class TokenListTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
        self::$db->getTokenListCollection()->drop();
    }

    public static function tearDownAfterClass(): void
    {
        self::$db->getTokenListCollection()->drop();
    }

    protected function tearDown(): void
    {
        self::$db->getTokenListCollection()->drop();
    }

    public function testGivenDataThenSave(): void
    {
        // Arrange
        $tokenName = 'Test10';

        self::$db->getTokenListCollection()->insertOne([
            'token_name' => $tokenName,
            'info' => '',
        ]);

        TokenList::SetInfo($tokenName, 'test');

        // Act
        TokenList::_save();

        // Assert
        $actual = self::$db->getTokenListCollection()->findOne(['token_name' => $tokenName]);
        $this->assertSame('test', $actual['info']);
    }

    public function testGivenTokenListThenLoadData(): void
    {
        // Arrange
        TokenList::_reset();
        $address = NodeInfo::getAddress();
        $saveData = [
            [
                'token_name' => 'Test-11',
                'info' => [
                    'publisher' => $address,
                    'total_amount' => 100000
                ]
            ],
            [
                'token_name' => 'Test-12',
                'info' => [
                    'publisher' => $address,
                    'total_amount' => 200000
                ]
            ]
        ];

        self::$db->getTokenListCollection()->insertMany($saveData);

        TokenList::LoadTokenList($saveData[0]['token_name']);
        TokenList::LoadTokenList($saveData[1]['token_name']);

        // Act
        TokenList::_load();

        // Assert
        $tokenList = new TokenList();
        $allTokenNameList = $tokenList->getAllTokenNameList();
        $allTokenInfo = $tokenList->getAllTokenInfoList();

        $this->assertIsArray($allTokenNameList);
        $this->assertIsArray($allTokenInfo);

        $this->assertSame($saveData[0]['info'], $allTokenInfo[$saveData[0]['token_name']]);
        $this->assertSame($saveData[1]['info'], $allTokenInfo[$saveData[1]['token_name']]);

        $this->assertContains($saveData[0]['token_name'], $allTokenNameList);
        $this->assertContains($saveData[1]['token_name'], $allTokenNameList);
    }
}
