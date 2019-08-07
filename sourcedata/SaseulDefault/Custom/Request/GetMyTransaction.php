<?php

namespace Saseul\Custom\Request;

use Saseul\Common\Request;
use Saseul\Constant\MongoDbConfig;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\Parser;
use Saseul\Version;

class GetMyTransaction extends Request
{
    public const TYPE = 'GetMyTransaction';

    protected $request;
    protected $thash;
    protected $public_key;
    protected $signature;

    private $type;
    private $version;
    private $from;
    private $transactional_data;
    private $timestamp;

    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
        $this->request = $request;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $this->request['type'] ?? '';
        $this->version = $this->request['version'] ?? '';
        $this->from = $this->request['from'] ?? '';
        $this->transactional_data = $this->request['transactional_data'] ?? '';
        $this->timestamp = $this->request['timestamp'] ?? 0;
    }

    public function getValidity(): bool
    {
        return Version::isValid($this->version)
            && !empty($this->timestamp)
            && $this->type === self::TYPE
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    public function getResponse(): array
    {
        $db = Database::GetInstance();

        $namespace = MongoDbConfig::NAMESPACE_TRANSACTION;
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
