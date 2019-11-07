<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Rule;
use Saseul\Core\Env;
use Saseul\Core\NodeInfo;
use Saseul\System\Cache;
use Saseul\System\Database;
use Saseul\System\Key;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\Mongo;
use Saseul\Version;

class Genesis extends Script
{
    private $db;
    private $cache;

    private $genesis_message;

    private $noAsk;

    public function __construct($noAsk = false)
    {
        $this->db = Database::getInstance();
        $this->cache = Cache::GetInstance();

        $this->noAsk = $noAsk;
        $this->genesis_message = Env::$genesis['key'];
    }

    public function _process()
    {
        if ($this->noAsk === false) {
            if ($this->ask('Genesis? [y/n] ') !== 'y') {
                return;
            }
        }

        $this->CheckGenesis();
        $this->CreateKey();
        $this->CreateGenesisTransaction();

        Logger::EchoLog('Success ');
    }

    public function CheckGenesis()
    {
        Logger::EchoLog('CheckGenesis');
        $v = $this->cache->get('CheckGenesis');

        if ($v === false) {
            $this->cache->set('CheckGenesis', 'inProcess', 15);
        } else {
            $this->Error('There is genesis block already ');
        }

        $rs = $this->db->Command(Mongo::DB_COMMITTED, ['count' => Mongo::COLLECTION_BLOCKS]);

        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        if ($count > 0) {
            $this->Error('There is genesis block already ');
        }
    }

    public function CreateKey()
    {
        Logger::EchoLog('CreateKey');

        $this->data['node_key'] = [
            'private_key' => NodeInfo::getPrivateKey(),
            'public_key' => NodeInfo::getPublicKey(),
            'address' => NodeInfo::getAddress(),
        ];
    }

    public function CreateGenesisTransaction()
    {
        Logger::EchoLog('CreateGenesisTransaction');
        $transaction_genesis = [
            'version' => Version::CURRENT,
            'type' => 'Genesis',
            'from' => NodeInfo::getAddress(),
            'amount' => Env::$genesis['coin_amount'],
            'transactional_data' => $this->genesis_message,
            'timestamp' => DateTime::Microtime(),
        ];

        $transaction_deposit = [
            'version' => Version::CURRENT,
            'type' => 'Deposit',
            'from' => NodeInfo::getAddress(),
            'amount' => Env::$genesis['deposit_amount'],
            'fee' => 0,
            'transactional_data' => 'Genesis Deposit',
            'timestamp' => DateTime::Microtime(),
        ];

        $thash_genesis = hash('sha256', json_encode($transaction_genesis));
        $public_key_genesis = NodeInfo::getPublicKey();
        $signature_genesis = Key::makeSignature($thash_genesis, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());

        $this->AddAPIChunk([
            'transaction' => $transaction_genesis,
            'public_key' => $public_key_genesis,
            'signature' => $signature_genesis,
        ], $transaction_genesis['timestamp']);

        $thash_deposit = hash('sha256', json_encode($transaction_deposit));
        $public_key_deposit = NodeInfo::getPublicKey();
        $signature_deposit = Key::makeSignature($thash_deposit, NodeInfo::getPrivateKey(), NodeInfo::getPublicKey());

        $this->AddAPIChunk([
            'transaction' => $transaction_deposit,
            'public_key' => $public_key_deposit,
            'signature' => $signature_deposit,
        ], $transaction_deposit['timestamp']);
    }

    public function AddAPIChunk($transaction, $timestamp)
    {
        $filename = Directory::API_CHUNKS . '/' . $this->GetID($timestamp) . '.json';

        $file = fopen($filename, 'a');
        fwrite($file, json_encode($transaction) . ",\n");
        fclose($file);
    }

    public function GetID($timestamp)
    {
        $tid = $timestamp - ($timestamp % Rule::MICROINTERVAL_OF_CHUNK) + Rule::MICROINTERVAL_OF_CHUNK;

        return preg_replace('/0{6}$/', '', $tid);
    }
}
