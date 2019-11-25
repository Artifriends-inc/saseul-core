<?php

namespace Saseul\Custom\Status;

use Exception;
use Saseul\Common\Status;
use Saseul\System\Database;
use Saseul\Util\Parser;

class TokenList implements Status
{
    protected static $token_names = [];
    protected static $token_info = [];

    public static function LoadTokenList($token_name)
    {
        self::$token_names[] = $token_name;
    }

    public static function getInfo($token_name)
    {
        if (isset(self::$token_info[$token_name])) {
            return self::$token_info[$token_name];
        }

        return [];
    }

    /**
     * 불러오기 위해서 입력된 모든 Token name 을 반환한다.
     *
     * @return array
     */
    public function getAllTokenNameList(): array
    {
        return self::$token_names;
    }

    /**
     * Token 정보가 있는 모든 Token 정보를 반환한다.
     *
     * @return array
     */
    public function getAllTokenInfoList(): array
    {
        return self::$token_info;
    }

    public static function SetInfo($token_name, $info)
    {
        self::$token_info[$token_name] = $info;
    }

    /**
     * Status 값을 초기화한다.
     */
    public static function _reset(): void
    {
        self::$token_names = [];
        self::$token_info = [];
    }

    /**
     * 저장되어 있는 Status 값을 읽어온다.
     */
    public static function _load(): void
    {
        self::$token_names = array_values(array_unique(self::$token_names));

        if (count(self::$token_names) === 0) {
            return;
        }

        $db = Database::getInstance();
        $filter = ['token_name' => ['$in' => self::$token_names]];
        $cursor = $db->getTokenListCollection()->find($filter);

        foreach ($cursor as $item) {
            if (isset($item->info)) {
                self::$token_info[$item->token_name] = Parser::objectToArray($item->info);
            }
        }
    }

    /**
     * Status 값을 전처리한다.
     */
    public static function _preprocess(): void
    {
    }

    /**
     * Status 값을 저장한다.
     *
     * @throws Exception
     */
    public static function _save(): void
    {
        $db = Database::getInstance();

        $operations = [];
        foreach (self::$token_info as $tokenName => $info) {
            $operations[] = [
                'updateOne' => [
                    ['token_name' => $tokenName],
                    ['$set' => ['info' => $info]],
                    ['upsert' => true],
                ]
            ];
        }

        if (empty($operations)) {
            return;
        }

        $db->getTokenListCollection()->bulkWrite($operations);

        self::_reset();
    }

    /**
     * Status 값을 후처리한다.
     */
    public static function _postprocess(): void
    {
    }
}
