<?php

namespace Saseul\Custom\Method;

use Exception;
use Saseul\System\Database;

class Token
{
    /**
     * 모든 토큰 정보를 가져온다.
     *
     * @param array      $addresses   Token 정보를 가져올 Account address 목록
     * @param null|array $token_names Token 정보를 가져올 Token 이름 목록
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getAll(array $addresses, array $token_names = null): array
    {
        $db = Database::getInstance();
        $all = static::initEmptyArray($addresses);

        $filter = ['address' => ['$in' => $addresses]];

        if ($token_names !== null) {
            $tokenFilter = ['token_name' => ['$in' => $token_names]];
            $filter = array_merge($filter, $tokenFilter);
        }

        $cursor = $db->getTokenCollection()->find($filter);

        foreach ($cursor as $item) {
            $all[$item->address][] = [
                'name' => $item->token_name,
                'balance' => (int) $item->balance
            ];
        }

        return $all;
    }

    /**
     * 입력된 목록으로 key를 삼아 값에 빈 배열을 추가하여 배열을 만든다.
     *
     * @param array $itemList key 값으로 만들 값 목록
     *
     * @return array
     */
    private static function initEmptyArray(array $itemList): array
    {
        $initArray = [];

        foreach ($itemList as $item) {
            $initArray[$item] = [];
        }

        return $initArray;
    }
}
