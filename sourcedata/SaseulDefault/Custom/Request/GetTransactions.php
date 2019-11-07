<?php

namespace Saseul\Custom\Request;

use Saseul\Constant\MongoDb;
use Saseul\System\Database;
use Saseul\Util\Logger;
use Saseul\Util\Parser;

/**
 * Class GetTransactions
 * 가지고 있는 transaction 을 제공한다.
 */
class GetTransactions extends AbstractRequest
{
    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getLogger(Logger::API);
    }

    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
        parent::initialize($request, $thash, $public_key, $signature);

        $this->limit = (int) (($request['limit'] !== '0') ? $request['limit'] : '10');
        $this->offset = (int) $request['offset'];
    }

    public function getResponse(): array
    {
        $db = Database::getInstance();

        $cursor = $db->getTransactionsCollection()->find(
            [],
            [
                'sort' => ['timestamp' => MongoDb::DESC],
                'limit' => $this->limit,
                'skip' => ($this->limit * $this->offset),
            ]
        );

        $transactions = [];
        foreach ($cursor as $item) {
            $item = Parser::objectToArray($item);
            unset($item['_id']);
            $transactions[] = $item;
        }

        return $transactions;
    }
}
