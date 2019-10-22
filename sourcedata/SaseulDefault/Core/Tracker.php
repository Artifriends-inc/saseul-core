<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Rank;
use Saseul\Constant\Role;
use Saseul\System\Database;
use Saseul\Util\Logger;
use Saseul\Util\Parser;

class Tracker
{
    /**
     * Tracker 를 초기화한다.
     * Daemon 을 실행할때만 사용한다.
     */
    public static function init(): void
    {
        self::resetBanList();
    }

    public static function resetBanList()
    {
        self::updateData(['status' => 'ban'], ['status' => 'admitted']);
    }

    public static function banList()
    {
        return self::GetNode(['status' => 'ban']);
    }

    public static function banHost($host)
    {
        self::updateData(['host' => $host], ['status' => 'ban']);
    }

    public static function GetNode($query)
    {
        $db = Database::getInstance();
        $rs = $db->Query(MongoDb::NAMESPACE_TRACKER, $query);
        $nodes = [];

        foreach ($rs as $item) {
            $node = [
                'host' => $item->host ?? '',
                'address' => $item->address ?? '',
                'rank' => $item->rank ?? Rank::LIGHT,
                'status' => $item->status ?? 'none',
                'my_observed_status' => $item->my_observed_status ?? 'none'
            ];

            $nodes[] = $node;
        }

        return $nodes;
    }

    public static function GetNodeAddress($query)
    {
        $db = Database::getInstance();
        $rs = $db->Query(MongoDb::NAMESPACE_TRACKER, $query);
        $nodes = [];

        foreach ($rs as $item) {
            $node = Parser::objectToArray($item);
            unset($node['_id']);
            $nodes[] = $node['address'];
        }

        return $nodes;
    }

    public static function getAccessibleNodes()
    {
        return self::GetNode(['host' => ['$nin' => [null, '']], 'status' => ['$ne' => 'ban']]);
    }

    public static function getAccessibleValidators()
    {
        return self::GetNode(['rank' => Rank::VALIDATOR, 'host' => ['$nin' => [null, '']], 'status' => ['$ne' => 'ban']]);
    }

    public static function GetValidatorAddress()
    {
        return self::GetNodeAddress(['rank' => Rank::VALIDATOR]);
    }

    public static function GetSupervisorAddress()
    {
        return self::GetNodeAddress(['rank' => Rank::SUPERVISOR]);
    }

    public static function GetArbiterAddress()
    {
        return self::GetNodeAddress(['rank' => Rank::ARBITER]);
    }

    public static function GetFullNodeAddress()
    {
        return self::GetNodeAddress(['rank' => ['$in' => Rank::FULL_NODES]]);
    }

    /**
     * 입력한 address 를 가진 node가 validator 인지 확인한다.
     *
     * @param $address
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isValidator($address): bool
    {
        $db = Database::getInstance();

        $filter = [
            'address' => $address,
            'rank' => Role::VALIDATOR,
        ];
        $count = $db->getTrackerCollection()->countDocuments($filter);

        return $count > 0;
    }

    public static function SetValidator($address)
    {
        self::setRank($address, Rank::VALIDATOR);
    }

    public static function SetSupervisor($address)
    {
        self::setRank($address, Rank::SUPERVISOR);
    }

    public static function SetArbiter($address)
    {
        self::setRank($address, Rank::ARBITER);
    }

    public static function SetLightNode($address)
    {
        self::setRank($address, Rank::LIGHT);
    }

    public static function setRank($address, $rank)
    {
        self::setData(['address' => $address], ['rank' => $rank, 'status' => 'none']);
    }

    public static function GetRole($address): string
    {
        $db = Database::getInstance();
        $role = Role::LIGHT;
        $query = ['address' => $address];

        $rs = $db->Query(MongoDb::NAMESPACE_TRACKER, $query);

        foreach ($rs as $item) {
            $role = $item->rank ?? Role::LIGHT;

            break;
        }

        return $role;
    }

    public static function GetRandomValidator()
    {
        $validators = self::getAccessibleValidators();
        $count = count($validators);
        $pick = rand(0, $count - 1);

        if (count($validators) > 0) {
            return $validators[$pick];
        }

        return [];
    }

    public static function setData($filter, $item)
    {
        $db = Database::getInstance();

        $opt = ['upsert' => true];
        $db->bulk->update($filter, ['$set' => $item], $opt);

        $db->BulkWrite(MongoDb::NAMESPACE_TRACKER);
    }

    public static function setHosts($infos): void
    {
        $db = Database::getInstance();

        foreach ($infos as $info) {
            $host = $info['host'];
            $address = $info['address'];

            // ignore my info;
            if ($address === NodeInfo::getAddress() || $host === NodeInfo::getHost()) {
                continue;
            }

            $db->bulk->update(['host' => $host, 'address' => ['$nin' => [null, '']]], ['$set' => ['host' => '']], ['multi' => true]);
            $db->bulk->update(['address' => $address], ['$set' => ['host' => $host]], ['upsert' => true]);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDb::NAMESPACE_TRACKER);
        }
    }

    public static function setMyHost()
    {
        $db = Database::getInstance();
        $host = NodeInfo::getHost();
        $address = NodeInfo::getAddress();

        $db->bulk->update(['host' => $host, 'address' => ['$nin' => [null, '']]], ['$set' => ['host' => '']], ['multi' => true]);
        $db->bulk->update(['address' => $address], ['$set' => ['host' => $host]], ['upsert' => true]);

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDb::NAMESPACE_TRACKER);
        }
    }

    public static function registerRequest($infos): void
    {
        // Todo: 변수명에 대해서 리팩토링이 필요하다.
        $newRequest = array_merge(Property::registerRequest(), $infos);
        $newRequest = array_unique(array_map(function ($obj) {
            return json_encode($obj);
        }, $newRequest));
        $newRequest = array_map(function ($obj) {
            return json_decode($obj, true);
        }, $newRequest);

        Property::registerRequest($newRequest);
    }

    /**
     * Reset 스크립트에서 Genesis Tracker 정보를 저장한다.
     */
    public static function reset(): void
    {
        $db = Database::getInstance();

        // Todo: 해당 부분을 basmith 에서 추가할 수 있도록 해야한다.
        if (NodeInfo::getAddress() === Env::$genesis['address']) {
            $db->bulk->insert([
                'host' => NodeInfo::getHost(),
                'address' => Env::$genesis['address'],
                'rank' => Rank::VALIDATOR,
                'status' => 'admitted',
            ]);
        } else {
            $db->bulk->insert([
                'host' => '',
                'address' => Env::$genesis['address'],
                'rank' => Rank::VALIDATOR,
                'status' => 'admitted',
            ]);

            $db->bulk->insert([
                'host' => NodeInfo::getHost(),
                'address' => NodeInfo::getAddress(),
                'rank' => Rank::LIGHT,
                'status' => 'admitted',
            ]);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDb::NAMESPACE_TRACKER);
        }
    }

    /**
     * Tracker 등록시 Genesis 노드인지를 확인하여 아니라면 Genesis Address 를 명시해준다.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function addTrackerOnDb(): string
    {
        $db = Database::getInstance();
        $role = Rank::LIGHT;
        $dbData = [];

        if (Env::$nodeInfo['address'] === Env::$genesis['address']) {
            $role = Rank::VALIDATOR;
        }

        if (Env::$nodeInfo['address'] !== Env::$genesis['address']) {
            $dbData[] = [
                'host' => '',
                'address' => Env::$genesis['address'],
                'rank' => Rank::VALIDATOR,
                'status' => 'admitted',
            ];
        }

        $dbData[] = [
            'host' => Env::$nodeInfo['host'],
            'address' => Env::$nodeInfo['address'],
            'rank' => $role,
            'status' => 'admitted',
        ];

        $db->getTrackerCollection()->insertMany($dbData);

        return $role;
    }

    /**
     * 입력받은 데이터를 업데이트한다.
     *
     * @param array $filter DB 쿼리문
     * @param array $update 업데이트할 데이터
     *
     * @throws Exception
     */
    private static function updateData(array $filter, array $update)
    {
        $db = Database::getInstance();

        $db->getTrackerCollection()->updateMany(
            $filter,
            ['$set' => $update],
        );
    }

    private static function logger()
    {
        return Logger::getLogger('Daemon');
    }
}
