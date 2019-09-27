<?php

namespace Saseul\Core;

use Saseul\Constant\MongoDb;
use Saseul\Constant\Rule;
use Saseul\Constant\Structure;
use Saseul\System\Database;
use Saseul\Util\DateTime;
use Saseul\Util\Mongo;
use Saseul\Util\Parser;

class Block
{
    public static function genesisHash(): string
    {
        return hash('sha256', json_encode(Env::$genesis['key']));
    }

    public static function generationOriginNumber(int $roundNumber)
    {
        $originNumber = ($roundNumber - ($roundNumber % Rule::GENERATION) - 1);

        if ($originNumber < 0) {
            $originNumber = 0;
        }

        return $originNumber;
    }

    public static function bunchFinalNumber(int $roundNumber)
    {
        return $roundNumber - ($roundNumber % Rule::BUNCH) + Rule::BUNCH - 1;
    }

    public static function lastBlock(): array
    {
        $opt = ['sort' => ['timestamp' => -1]];

        return self::data(MongoDb::NAMESPACE_BLOCK, [], $opt);
    }

    public static function nextBlock(array $lastBlock, string $blockhash, int $txCount, int $standardTimestamp): array
    {
        return [
            'block_number' => ((int) $lastBlock['block_number'] + 1),
            'last_blockhash' => $lastBlock['blockhash'],
            'blockhash' => $blockhash,
            'transaction_count' => $txCount,
            's_timestamp' => $standardTimestamp,
            'timestamp' => DateTime::Microtime(),
        ];
    }

    public static function lastBlocks(int $max_count = 100): array
    {
        $opt = ['sort' => ['timestamp' => -1]];

        return self::datas(MongoDb::NAMESPACE_BLOCK, $max_count, [], $opt);
    }

    public static function blockByNumber(int $block_number): array
    {
        $query = ['block_number' => $block_number];

        return self::data(MongoDb::NAMESPACE_BLOCK, $query);
    }

    public static function data(string $namespace, array $query = [], array $opt = []): array
    {
        $block = Structure::BLOCK;

        $blocks = self::datas($namespace, 1, $query, $opt);

        if (isset($blocks[0])) {
            $block = $blocks[0];
        }

        return $block;
    }

    public static function datas(string $namespace, int $max_count, array $query = [], array $opt = []): array
    {
        $db = Database::getInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);

            $datas[] = [
                'block_number' => (int) $item['block_number'] ?? 0,
                'last_blockhash' => $item['last_blockhash'] ?? '',
                'blockhash' => $item['blockhash'] ?? '',
                'transaction_count' => (int) $item['transaction_count'] ?? 0,
                's_timestamp' => (int) $item['s_timestamp'] ?? 0,
                'timestamp' => (int) $item['timestamp'] ?? 0,
            ];

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }

    public static function GetLastBlocks(int $max_count = 100): array
    {
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        return self::GetDatas(MongoDb::NAMESPACE_BLOCK, $max_count, $query, $opt);
    }

    public static function GetLastTransactions(int $max_count = 100): array
    {
        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];

        return self::GetDatas(MongoDb::NAMESPACE_TRANSACTION, $max_count, $query, $opt);
    }

    public static function GetDatas(string $namespace, int $max_count, array $query = [], array $opt = []): array
    {
        $db = Database::getInstance();
        $rs = $db->Query($namespace, $query, $opt);
        $datas = [];

        foreach ($rs as $item) {
            $data = Parser::objectToArray($item);
            unset($data['_id']);
            $datas[] = $data;

            if (count($datas) >= $max_count) {
                break;
            }
        }

        return $datas;
    }

    public static function txFileName(int $block_number)
    {
        $block = self::GetBlockByNumber($block_number);

        if (isset($block['blockhash'], $block['s_timestamp'])) {
            return $block['blockhash'] . $block['s_timestamp'];
        }

        return '';
    }

    public static function GetBlockByNumber(int $block_number)
    {
        $query = ['block_number' => $block_number];
        $blocks = self::GetDatas(MongoDb::NAMESPACE_BLOCK, 1, $query);

        if (isset($blocks[0])) {
            return $blocks[0];
        }

        return Structure::BLOCK;
    }

    public static function GetLastBlock()
    {
        $db = Database::getInstance();

        $ret = [
            'block_number' => 0,
            'last_blockhash' => '',
            'blockhash' => '',
            'transaction_count' => 0,
            's_timestamp' => 0,
            'timestamp' => 0,
        ];

        $query = [];
        $opt = ['sort' => ['timestamp' => -1]];
        $rs = $db->Query(MongoDb::NAMESPACE_BLOCK, $query, $opt);

        // Todo: 코드 정리하자.
        foreach ($rs as $item) {
            $item = Parser::objectToArray($item);

            if (isset($item['block_number'])) {
                $ret['block_number'] = $item['block_number'];
            }
            if (isset($item['last_blockhash'])) {
                $ret['last_blockhash'] = $item['last_blockhash'];
            }
            if (isset($item['blockhash'])) {
                $ret['blockhash'] = $item['blockhash'];
            }
            if (isset($item['transaction_count'])) {
                $ret['transaction_count'] = $item['transaction_count'];
            }
            if (isset($item['s_timestamp'])) {
                $ret['s_timestamp'] = $item['s_timestamp'];
            }
            if (isset($item['timestamp'])) {
                $ret['timestamp'] = $item['timestamp'];
            }

            break;
        }

        return $ret;
    }

    /**
     * Block 총수를 가져온다.
     *
     * @return int
     */
    public static function getCount(): int
    {
        $db = Database::getInstance();

        $command = [
            'count' => Mongo::COLLECTION_BLOCKS,
            'query' => [],
        ];

        $rs = $db->Command(Mongo::DB_COMMITTED, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        return $count;
    }
}
