<?php

namespace Saseul\Core;

use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Rank;
use Saseul\Constant\Role;
use Saseul\System\Database;
use Saseul\Util\Parser;

class Tracker
{
    public static function init() {
        $infos = [];
        $infos[] = [
            'host' => NodeInfo::getHost(),
            'address' => NodeInfo::getAddress(),
        ];

        self::setHosts($infos);
        self::resetBanList();
    }

    public static function resetBanList() {
        self::updateData(['status' => 'ban'], ['status' => 'admitted']);
    }

    public static function banList() {
        return self::GetNode(['status' => 'ban']);
    }

    public static function banHost($host)
    {
        self::updateData(['host' => $host], ['status' => 'ban']);
    }

    public static function GetNode($query)
    {
        $db = Database::GetInstance();
        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);
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
        $db = Database::GetInstance();
        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);
        $nodes = [];

        foreach ($rs as $item) {
            $node = Parser::objectToArray($item);
            unset($node['_id']);
            $nodes[] = $node['address'];
        }

        return $nodes;
    }

    public static function IsNode($address, $query)
    {
        $db = Database::GetInstance();
        $query = array_merge(['address' => $address], $query);
        $command = [
            'count' => MongoDbConfig::COLLECTION_TRACKER,
            'query' => $query,
        ];

        $rs = $db->Command(MongoDbConfig::DB_TRACKER, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;

            break;
        }

        if ($count > 0) {
            return true;
        }

        return false;
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

    public static function IsValidator($address)
    {
        return self::IsNode($address, ['rank' => Rank::VALIDATOR]);
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
        $db = Database::GetInstance();
        $role = Role::LIGHT;
        $query = ['address' => $address];

        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);

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

    public static function setData($filter, $item) {
        $db = Database::GetInstance();

        $opt = ['upsert' => true];
        $db->bulk->update($filter, ['$set' => $item], $opt);

        $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
    }

    public static function setHosts($infos): void
    {
        $db = Database::GetInstance();

        foreach ($infos as $info) {
            $host = $info['host'];
            $address = $info['address'];

            # ignore my info;
            if ($address === NodeInfo::getAddress() || $host === NodeInfo::getHost()) {
                continue;
            }

            $db->bulk->update(['host' => $host, 'address' => ['$nin' => [null, '']]], ['$set' => ['host' => '']], ['multi' => true]);
            $db->bulk->update(['address' => $address], ['$set' => ['host' => $host]], ['upsert' => true]);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }
    }

    public static function setMyHost() {

        $db = Database::GetInstance();
        $host = NodeInfo::getHost();
        $address = NodeInfo::getAddress();

        $db->bulk->update(['host' => $host, 'address' => ['$nin' => [null, '']]], ['$set' => ['host' => '']], ['multi' => true]);
        $db->bulk->update(['address' => $address], ['$set' => ['host' => $host]], ['upsert' => true]);

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }
    }

    public static function registerRequest($infos): void
    {
        $newRequest = array_merge(Property::registerRequest(), $infos);
        $newRequest = array_unique(array_map(function ($obj) { return json_encode($obj); }, $newRequest));
        $newRequest = array_map(function ($obj) { return json_decode($obj, true); }, $newRequest);

        Property::registerRequest($newRequest);
    }

    public static function reset() {
        $db = Database::GetInstance();

        if (NodeInfo::getAddress() === Env::$genesis['address']) {
            $db->bulk->insert([
                'host' => NodeInfo::getHost(),
                'address' => Env::$genesis['address'],
                'rank' => Rank::VALIDATOR,
                'status' => 'admitted',
            ]);
        } else {
            $db->bulk->insert([
                'host' => Env::$genesis['host'],
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
            $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }
    }

    public static function updateData($filter, $item) {
        $db = Database::GetInstance();

        $db->bulk->update($filter, ['$set' => $item], ['multi' => true]);

        $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
    }
}
