<?php

namespace Saseul\Script\Transaction;

use Saseul\Common\Script;
use Saseul\Core\NodeInfo;
use Saseul\Core\Tracker;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;
use Saseul\Version;

class Deposit extends Script
{
    private $rest;

    private $m_result;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _process()
    {
        static::log()->info('Type amount to deposit coin.');
        $amount = trim(fgets(STDIN));

        $validator = Tracker::GetRandomValidator();

        if (!isset($validator['host'])) {
            return;
        }

        $host = $validator['host'];

        $transaction = [
            'type' => 'Deposit',
            'version' => Version::CURRENT,
            'from' => NodeInfo::getAddress(),
            'amount' => $amount,
            'fee' => 0,
            'transactional_data' => '',
            'timestamp' => DateTime::Microtime(),
        ];

        $thash = hash('sha256', json_encode($transaction));
        $public_key = NodeInfo::getPublicKey();
        $signature = Key::makeSignature($thash, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());

        $url = "http://{$host}/transaction";
        $ssl = false;
        $data = [
            'transaction' => json_encode($transaction),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        $result = $this->rest->POST($url, $data, $ssl, $header);
        $this->m_result = json_decode($result, true);
    }

    public function _end()
    {
        $this->data['result'] = $this->m_result;
    }
}
