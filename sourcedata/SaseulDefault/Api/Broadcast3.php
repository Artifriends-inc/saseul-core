<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Constant\Structure;
use Saseul\Core\Chunk;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\Cache;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\TypeChecker;

class Broadcast3 extends Api
{
    private $cache;

    private $broadcast_code;
    private $s_timestamp;
    private $req_time;
    private $public_key;
    private $signature;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
    }

    public function _init()
    {
        $this->broadcast_code = $this->getParam($_REQUEST, 'broadcast_code', ['default' => '']);
        $this->s_timestamp = (int)$this->getParam($_REQUEST, 's_timestamp', ['default' => 0]);
        $this->req_time = (int) $this->getParam($_REQUEST, 'req_time');
        $this->public_key = $this->getParam($_REQUEST, 'public_key');
        $this->signature = $this->getParam($_REQUEST, 'signature');
    }

    public function _process()
    {
        $this->checkParam();

        $myBroadcastCode = Chunk::broadcastCode(DateTime::toTime($this->s_timestamp));
        $items = $this->pickItems($myBroadcastCode);

        $this->data = [
            'items' => $items,
            'broadcast_code' => $myBroadcastCode,
            'address' => NodeInfo::getAddress(),
        ];
    }

    public function pickItems($myBroadcastCode)
    {
        $items = [];
        $needles = [];
        $validators = Tracker::GetValidatorAddress();
        sort($validators);

        if (count($validators) !== mb_strlen($this->broadcast_code)) {
            return $items;
        }

        for ($i = 0; $i < mb_strlen($this->broadcast_code); $i++) {
            if ($this->broadcast_code[$i] === '0' && $myBroadcastCode[$i] === '1') {
                $needles[] = $validators[$i];
            }
        }

        shuffle($needles);

        if (count($needles) === 0) {
            return $items;
        }

        $address = array_pop($needles);
        $fileName = "{$address}_" . DateTime::toTime($this->s_timestamp);
        $broadcastChunk = Chunk::broadcastChunk($fileName);

        $item = [
            'address' => $address,
            'file_name' => $fileName,
            'transactions' => $broadcastChunk['transactions'],
            'public_key' => $broadcastChunk['public_key'],
            'content_signature' => $broadcastChunk['content_signature'],
        ];

        $items[] = $item;

        return $items;
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
