<?php

namespace Saseul\Custom\Method;

use Exception;
use Saseul\System\Database;

/**
 * Class Coin provides functions related to the coin used by the API.
 */
class Coin
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 입력받은 Account address 에 대한 Coin 정보를 반환한다.
     *
     * @param array $addresses Coin 정보를 확인할 Account address 목록
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getAll(array $addresses): array
    {
        $filter = ['address' => ['$in' => $addresses]];
        $cursor = (new self())->db->getCoinCollection()->find($filter);

        $coinData = (new self())->initEmptyArray($addresses);
        foreach ($cursor as $item) {
            $coinData[$item->address]['balance'] = $item->balance ?? 0;
            $coinData[$item->address]['deposit'] = $item->deposit ?? 0;
        }

        return $coinData;
    }

    /**
     * 입력된 목록을 Key 를 삼아 값에 빈 배열을 추가하여 배열을 생성한다.
     *
     * @param array $itemList key 값으로 만들 값 목록
     *
     * @return array
     */
    private function initEmptyArray(array $itemList): array
    {
        $initArray = [];
        foreach ($itemList as $item) {
            $initArray[$item] = [
                'balance' => 0,
                'deposit' => 0,
            ];
        }

        return $initArray;
    }
}
