<?php

namespace Saseul\Custom\Request;

use Exception;
use Saseul\System\Database;
use Saseul\Util\Logger;
use Saseul\Util\Parser;

/**
 * Class GetMyTransaction.
 * 요청한 Account 에 대한 Transaction 정보를 가져온다.
 * 정렬: 시간 역순.
 */
class GetMyTransaction extends AbstractRequest
{
    /** @var string */
    private $limit;

    /** @var string */
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

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getResponse(): array
    {
        $db = Database::getInstance();

        $cursor = $db->getTransactionsCollection()->find(
            ['public_key' => $this->public_key],
            [
                'sort' => ['timestamp' => -1],
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
