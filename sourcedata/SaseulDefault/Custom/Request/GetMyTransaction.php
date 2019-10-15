<?php

namespace Saseul\Custom\Request;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;
use Saseul\Util\Parser;

class GetMyTransaction extends AbstractRequest
{
    public function getResponse(): array
    {
        $db = Database::getInstance();

        $namespace = MongoDb::NAMESPACE_TRANSACTION;
//        $filter = ['public_key' => Config::$node_public_key];
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
            $count = $count + 1;

            if ($count >= $max) {
                break;
            }
        }

        return $transactions;
    }
}
