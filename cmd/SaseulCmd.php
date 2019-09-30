<?php
declare(strict_types=1);

namespace Cmd;

require_once 'vendor/autoload.php';

use Composer\Script\Event;
use GuzzleHttp\Client;
use Saseul\System\Key;
use Saseul\Util\DateTime;

class SaseulCmd
{
    public function echo(Event $event): void
    {
        echo 'hi\n';
    }

    /**
     * MongoDB 를 초기화한다.
     *
     * @param Event $event
     */
    public static function initData(Event $event): void
    {
        $privateKey = getenv('NODE_PRIVATE_KEY');
        $publicKey = getenv('NODE_PUBLIC_KEY');
        $address = getenv('NODE_ADDRESS');
        $host = getenv('NODE_HOST');

        $initDbData = [
            'type' => 'InitDatabase',
            'from' => $address,
            'timestamp' => DateTime::Microtime(),
        ];
        $thash = hash('sha256', json_encode($initDbData, JSON_THROW_ON_ERROR, 512));
        $requestInitDb = [
            'resource' => json_encode($initDbData, JSON_THROW_ON_ERROR, 512),
            'public_key' => $publicKey,
            'signature' => Key::makeSignature($thash, $privateKey, $publicKey),
        ];

        $client = new Client(['base_uri' => "http://{$host}"]);
        $res = $client->post('/resource', ['json' => $requestInitDb])->getBody();
        echo $res . PHP_EOL;
    }
}
