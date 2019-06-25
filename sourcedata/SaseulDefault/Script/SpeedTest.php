<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Rule;
use Saseul\Util\Logger;
use Saseul\Version;
use Saseul\Core\NodeInfo;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\RestCall;

class SpeedTest extends Script
{
    private $rest;

    public function __construct()
    {
        $this->rest = RestCall::GetInstance();
    }

    public function _process()
    {
        $accounts = $this->accounts();
        $round = 1;

        if (isset($this->arg[0]) && is_numeric($this->arg[0])) {
            $round = $this->arg[0];
        }

        for ($i = 0; $i < $round; $i++) {
            foreach ($accounts as $address) {
                $this->SendCoin($address, 120000);
//                usleep(20000);
                usleep(10000);
            }
        }
    }

    public function accounts()
    {
        $accounts = [];

        for ($i = 0; $i < 100; $i++) {
            $pv = Key::makePrivateKey();
            $pub = Key::makePublicKey($pv);
            $addr = Key::makeAddress($pub);

            $accounts[] = $addr;
        }

        return $accounts;
    }

    public function SendCoin($to, $amount)
    {
        $host = NodeInfo::getHost();

        $transaction = [
            'type' => 'SendCoin',
            'version' => Version::CURRENT,
            'from' => NodeInfo::getAddress(),
            'to' => $to,
            'amount' => $amount,
            'fee' => (int) ($amount * Rule::FEE_RATE),
            'transactional_data' => '',
            'timestamp' => DateTime::Microtime(),
        ];

        $thash = hash('sha256', json_encode($transaction));
        $public_key = NodeInfo::getPublicKey();
        $signature = Key::makeSignature(
            $thash,
            NodeInfo::getPrivateKey(),
            NodeInfo::getPublicKey()
        );

        $url = "http://{$host}/transaction";
        $ssl = false;
        $data = [
            'transaction' => json_encode($transaction),
            'public_key' => $public_key,
            'signature' => $signature,
        ];
        $header = [];

        $rs = $this->rest->POST($url, $data, $ssl, $header);
    }
}