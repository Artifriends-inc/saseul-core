<?php

namespace Saseul\Custom\Request;

use Exception;
use Saseul\System\Database;
use Saseul\Util\Parser;

/**
 * Class GetTransaction.
 * 요청한 Transaction hash 를 이용하여 transaction 을 찾는다.
 */
class GetTransaction extends AbstractRequest
{
    /** @var string Finding transaction hash */
    private $find_thash;

    public function initialize(
        array $request,
        string $thash,
        string $public_key,
        string $signature
    ): void {
        parent::initialize($request, $thash, $public_key, $signature);

        $this->find_thash = $request['thash'] ?? '';
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function getResponse(): array
    {
        $db = Database::getInstance();

        $cursor = $db->getTransactionsCollection()->findOne(
            ['thash' => $this->find_thash],
        );
        $transaction = Parser::objectToArray($cursor);
        unset($transaction['_id']);

        return $transaction;
    }
}
