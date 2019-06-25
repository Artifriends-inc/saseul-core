<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\Cache;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class Broadcast2 extends Api
{
    private $cache;

    private $min_time;
    private $max_time;
    private $req_time;
    private $public_key;
    private $signature;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
    }

    public function _init()
    {
        $this->min_time = (int) $this->getParam($_REQUEST, 'min_time', ['default' => 0]);
        $this->max_time = (int) $this->getParam($_REQUEST, 'max_time', ['default' => DateTime::Microtime()]);
//        $this->req_time = (int) $this->getParam($_REQUEST, 'req_time');
//        $this->public_key = $this->getParam($_REQUEST, 'public_key');
//        $this->signature = $this->getParam($_REQUEST, 'signature');

        if ($this->max_time > DateTime::Microtime()) {
            $this->max_time = DateTime::Microtime();
        }
    }

    public function _process()
    {
//        $this->checkParam();

        $minTime = DateTime::toTime($this->min_time);
        $maxTime = DateTime::toTime($this->max_time);

        $address = NodeInfo::getAddress();
        $fileName = "{$address}_{$maxTime}";
        $txs = Chunk::txFromApiChunk($minTime, $maxTime);
        $cacheKey = "chunksig_{$fileName}";
        $publicKey = NodeInfo::getPublicKey();

        $contentSignature = Chunk::contentSignature($cacheKey, $maxTime, $txs);

        $item = [
            'address' => $address,
            'file_name' => $fileName,
            'transactions' => $txs,
            'public_key' => $publicKey,
            'content_signature' => $contentSignature,
        ];

        $this->data = [
            'items' => [$item]
        ];
    }

    public function checkParam()
    {
        if (!is_string($this->public_key) || !is_string($this->signature)) {
            $this->error('Invalid public key & signature.');
        }

        if (!in_array(Key::makeAddress($this->public_key), Tracker::GetValidatorAddress())) {
            $this->error('You are not validator. ');
        }

        if ((abs(DateTime::Microtime() - $this->req_time) > 5000000) ||
            !Key::isValidSignature($this->req_time, $this->public_key, $this->signature)) {
            $this->error('Invalid signature. ');
        }
    }
}
