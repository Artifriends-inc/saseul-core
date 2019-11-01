<?php

namespace Saseul\Custom\Request;

use MongoDB\Driver\Exception\Exception;
use Saseul\Constant\MongoDb;
use Saseul\System\Database;
use Saseul\Util\Parser;

/**
 * Class GetMyTransaction.
 * 요청한 Account 에 대한 Transaction 정보를 가져온다.
 */
class GetMyTransaction extends AbstractRequest
{
    /**
     * @throws Exception
     *
     * @return array (See below)
     */
    public function getResponse(): array
    {
        $db = Database::getInstance();

        $namespace = MongoDb::NAMESPACE_TRANSACTION;
        $filter = ['public_key' => $this->public_key];
        $opt = ['sort' => ['timestamp' => -1]];
        $rs = $db->Query($namespace, $filter, $opt);

        $max = 10;
        $count = 0;
        $transactions = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);
            unset($item['_id']);

            $transactions[] = $item;
            ++$count;

            if ($count >= $max) {
                break;
            }
        }

        return $transactions;
    }
}
