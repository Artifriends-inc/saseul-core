<?php
declare(strict_types=1);

namespace Cmd;

require_once 'vendor/autoload.php';

use Composer\Script\Event;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Saseul\Core\Env;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Version;

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
        $env = self::setEnv();

        $initDbData = [
            'type' => 'InitDatabase',
            'from' => $env['address'],
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $initDbData)->getBody();
        echo $res . PHP_EOL;
    }

    public static function setLightTracker(Event $event): void
    {
        $env = self::setEnv();

        $lightTrackerData = [
            'type' => 'SetTracker',
            'from' => $env['address'],
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $lightTrackerData)->getBody();
        echo $res . PHP_EOL;
    }

    public static function setValidatorTracker(Event $event): void
    {
        $env = self::setEnv();

        $initDbData = [
            'type' => 'InitDatabase',
            'from' => $env['address'],
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $initDbData)->getBody();
        echo $res . PHP_EOL;

        $validatorTrackerData = [
            'type' => 'SetTracker',
            'from' => $env['address'],
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $validatorTrackerData)->getBody();
        echo $res . PHP_EOL;
    }

    public static function makeGenesis(Event $event): void
    {
        $env = self::setEnv();
        $genesisEnv = self::setGenesisEnv();

        $genesisData = [
            'type' => 'Genesis',
            'version' => Version::CURRENT,
            'from' => $env['address'],
            'amount' => $genesisEnv['coin_amount'],
            'transactional_data' => $genesisEnv['key'],
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $genesisData)->getBody();
        echo $res . PHP_EOL;

        $depositData = [
            'type' => 'Deposit',
            'version' => Version::CURRENT,
            'from' => $env['address'],
            'amount' => $genesisEnv['deposit_amount'],
            'fee' => 0,
            'transactional_data' => 'Genesis deposit',
            'timestamp' => DateTime::Microtime(),
        ];
        $res = self::sendRequest($env, $depositData)->getBody();
        echo $res . PHP_EOL;
    }

    /**
     * env 값을 읽어온다.
     *
     * @return array
     */
    private static function setEnv(): array
    {
        // const
        define('SASEUL_DIR', dirname(__DIR__));
        define('ROOT_DIR', __DIR__);

        return [
            'private_key' => getenv('NODE_PRIVATE_KEY'),
            'public_key' => getenv('NODE_PUBLIC_KEY'),
            'address' => getenv('NODE_ADDRESS'),
            'host' => getenv('NODE_HOST'),
        ];
    }

    private static function setGenesisEnv(): array
    {
        return [
            'coin_amount' => getenv('GENESIS_COIN_VALUE'),
            'deposit_amount' => getenv('GENESIS_DEPOSIT_VALUE'),
            'key' => Env::loadGenesisKey('data/core/genesis_key.json'),
        ];
    }

    /**
     * request 데이터를 생성한다.
     *
     * @param array $env
     * @param array $data
     *
     * @return ResponseInterface
     */
    private static function sendRequest(array $env, array $data): ResponseInterface
    {
        $thash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $requestData =  [
            'resource' => json_encode($data, JSON_THROW_ON_ERROR),
            'public_key' => $env['public_key'],
            'signature' => Key::makeSignature($thash, $env['private_key'], $env['public_key']),
        ];

        $client = new Client(['base_uri' => "http://{$env['host']}"]);
        return $client->post('/resource', ['json' => $requestData]);
    }
}
