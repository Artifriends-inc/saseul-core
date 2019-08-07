<?php

// TODO: fork 됐을때 tracker 함부로 못들어오게 해야함.

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\TypeChecker;

class Round extends Api
{
    private $host_info;
    private $structure = [
        'host' => '',
        'timestamp' => 0,
        'signature' => '',
        'public_key' => '',
    ];

    public function _init()
    {
        $this->host_info = $this->getParam($_REQUEST, 'host_info', ['default' => '']);
    }

    public function _process()
    {
        if ($this->host_info !== '') {
            $hostInfo = json_decode($this->host_info, true);
            // sign 절차 거치도록 변경해야함.
            // 일단 signature 이용함.

            if (TypeChecker::StructureCheck($this->structure, $hostInfo)) {
                $string = $hostInfo['host'] . $hostInfo['timestamp'];

                if ((abs(DateTime::Microtime() - $hostInfo['timestamp']) > 5000000) ||
                    !Key::isValidSignature($string, $hostInfo['public_key'], $hostInfo['signature'])
                ) {
                    $this->error('invalid');
                }

                $address = Key::makeAddress($hostInfo['public_key']);

                $infos = [];
                $infos[] = [
                    'address' => $address,
                    'host' => $hostInfo['host'],
                ];

                Tracker::registerRequest($infos);
            }
        }

        $this->data = Property::round();
    }
}
