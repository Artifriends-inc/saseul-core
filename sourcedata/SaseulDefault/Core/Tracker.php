<?php

namespace Saseul\Core;

use Exception;
use Saseul\Constant\MongoDb;
use Saseul\Constant\Role;
use Saseul\Models\Tracker as TrackerModel;
use Saseul\System\Database;

class Tracker
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tracker 를 초기화한다.
     * Daemon 을 실행할때만 사용한다.
     */
    public static function init(): void
    {
        self::resetBanList();
    }

    public static function resetBanList(): void
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
                'role' => $item->role ?? Role::LIGHT,
                'status' => $item->status ?? 'none',
                'my_observed_status' => $item->my_observed_status ?? 'none'
            ];

            $nodes[] = $node;
        }

        return $nodes;
    }

    public static function getAccessibleNodes()
    {
        return self::GetNode(['host' => ['$nin' => [null, '']], 'status' => ['$ne' => 'ban']]);
    }

    public static function getAccessibleValidators()
    {
        return self::GetNode(
            ['role' => Role::VALIDATOR, 'host' => ['$nin' => [null, '']], 'status' => ['$ne' => 'ban']]
        );
    }

    /**
     * Validator tracker address 목록을 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getValidatorAddress(): array
    {
        return (new self())->getAddressByRole([Role::VALIDATOR]);
    }

    /**
     * Supervisor tracker address 목록을 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getSupervisorAddress(): array
    {
        return (new self())->getAddressByRole([Role::SUPERVISOR]);
    }

    /**
     * Arbiter tracker address 목록을 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getArbiterAddress(): array
    {
        return (new self())->getAddressByRole([Role::ARBITER]);
    }

    /**
     * Light 노드를 제외한 tracker address 목록을 반환한다.
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getFullNodeAddress(): array
    {
        return (new self())->getAddressByRole(Role::FULL_NODES);
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
            'role' => Role::VALIDATOR,
        ];
        $count = $db->getTrackerCollection()->countDocuments($filter);

        return $count > 0;
    }

    public static function SetValidator($address)
    {
        self::setRole($address, Role::VALIDATOR);
    }

    public static function SetSupervisor($address)
    {
        self::setRole($address, Role::SUPERVISOR);
    }

    public static function SetArbiter($address)
    {
        self::setRole($address, Role::ARBITER);
    }

    public static function SetLightNode($address)
    {
        self::setRole($address, Role::LIGHT);
    }

    /**
     * Role을 설정한다.
     *
     * @param string $address Node Account address
     * @param string $role    설정한 Role
     *
     * @throws Exception
     */
    public static function setRole(string $address, string $role): void
    {
        $db = Database::getInstance();

        $db->getTrackerCollection()->updateOne(
            ['address' => $address],
            ['$set' => ['role' => $role, 'status' => 'none']],
            ['upsert' => true]
        );
    }

    /**
     * Node Account address 로 해당 Node에 대한 role을 반환한다.
     *
     * @param string $address Node Account address
     *
     * @throws Exception
     *
     * @return string
     */
    public static function getRole(string $address): string
    {
        $db = Database::getInstance();
        $filter = ['address' => $address];
        $cursor = $db->getTrackerCollection()->findOne($filter);

        return $cursor->role ?? Role::LIGHT;
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

    /**
     * Host 정보를 업데이트한다.
     *
     * ### 제약 조건
     * - 하나의 Host는 두개이상의 Address를 가진 Node가 있을 수 없다.
     * - 나 자신의 정보는 업데이트 하지 않는다.
     *
     * @param array $nodeInfoList 알고 있는 Node 들의 정보
     *
     * @throws Exception
     */
    public static function setHosts(array $nodeInfoList): void
    {
        $db = Database::getInstance();

        if ((false !== ($key = array_search(NodeInfo::getAddress(), $nodeInfoList, true)))
            || (false !== ($key = array_search(NodeInfo::getHost(), $nodeInfoList, true)))) {
            unset($nodeInfoList[$key]);
        }

        $operations = [];
        foreach ($nodeInfoList as $info) {
            $host = $info['host'];
            $address = $info['address'];

            $operations[] = [
                'updateMany' => [
                    ['host' => $host, 'address' => ['$nin' => [null, '']]],
                    ['$set' => ['host' => '']],
                ],
            ];
            $operations[] = [
                'updateOne' => [
                    ['address' => $address],
                    ['$set' => ['host' => $host]],
                    ['upsert' => true],
                ]
            ];
        }

        $db->getTrackerCollection()->bulkWrite($operations);
    }

    /**
     * 해당 Node Tracker 정보를 DB에 저장한다.
     */
    public static function setMyHost(): void
    {
        $db = Database::getInstance();
        $host = NodeInfo::getHost();
        $address = NodeInfo::getAddress();

        $db->getTrackerCollection()->bulkWrite(
            [
                [
                    'updateMany' => [
                        ['host' => $host, 'address' => ['$nin' => [null, '']]],
                        ['$set' => ['host' => '']],
                    ]
                ],
                [
                    'updateOne' => [
                        ['address' => $address],
                        ['$set' => ['host' => $host]],
                        ['upsert' => true],
                    ]
                ],
            ]
        );
    }

    public static function registerRequest($infos): void
    {
        // Todo: 변수명에 대해서 리팩토링이 필요하다.
        $newRequest = array_merge(Property::registerRequest(), $infos);
        $newRequest = array_unique(
            array_map(
                function ($obj) {
                    return json_encode($obj);
                },
                $newRequest
            )
        );
        $newRequest = array_map(
            function ($obj) {
                return json_decode($obj, true);
            },
            $newRequest
        );

        Property::registerRequest($newRequest);
    }

    /**
     * Reset 스크립트에서 Genesis Tracker 정보를 저장한다.
     *
     * @deprecated Script에서만 사용하고 있기에 Script 항목 삭제시 같이 삭제될 예정.
     */
    public static function reset(): void
    {
        $db = Database::getInstance();

        // Todo: 해당 부분을 basmith 에서 추가할 수 있도록 해야한다.
        if (NodeInfo::getAddress() === Env::$genesis['address']) {
            $db->bulk->insert(
                [
                    'host' => NodeInfo::getHost(),
                    'address' => Env::$genesis['address'],
                    'role' => Role::VALIDATOR,
                    'status' => 'admitted',
                ]
            );
        } else {
            $db->bulk->insert(
                [
                    'host' => '',
                    'address' => Env::$genesis['address'],
                    'role' => Role::VALIDATOR,
                    'status' => 'admitted',
                ]
            );

            $db->bulk->insert(
                [
                    'host' => NodeInfo::getHost(),
                    'address' => NodeInfo::getAddress(),
                    'role' => Role::LIGHT,
                    'status' => 'admitted',
                ]
            );
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
        $role = Role::LIGHT;
        if (self::isGenesisNode()) {
            $role = Role::VALIDATOR;
        }

        $db = Database::getInstance();
        $trackerDocument = self::assembleTrackerDocument();
        $db->getTrackerCollection()->bulkWrite($trackerDocument);

        return $role;
    }

    /**
     * Tracker 정보를 저장하기 위한 Document 를 생성한다.
     *
     * @return array
     */
    private static function assembleTrackerDocument(): array
    {
        $filter = ['address' => ''];
        $update = ['$set' => ''];
        $role = Role::VALIDATOR;
        $status = 'admitted';

        if (self::isGenesisNode()) {
            $filter['address'] = NodeInfo::getAddress();
            $update['$set'] = new TrackerModel(
                NodeInfo::getHost(),
                NodeInfo::getAddress(),
                $role,
                $status
            );
        } else {
            $filter = [
                'address' => Env::$genesis['address'],
                'role' => Role::VALIDATOR,
            ];
            $update['$set'] = new TrackerModel(
                Env::$genesis['host'],
                Env::$genesis['address'],
                $role,
                $status
            );
        }

        $options = ['upsert' => true];
        return [['updateOne' => [$filter, $update, $options]]];
    }

    /**
     * 현재 노드가 genesis 노드인지 확인한다.
     *
     * @return bool
     */
    private static function isGenesisNode(): bool
    {
        return NodeInfo::getAddress() === Env::$genesis['address'];
    }

    /**
     * Role 에 맞는 Tracker 들의 Address를 반환한다.
     *
     * @param array $role Tracker Role
     *
     * @return array
     */
    private function getAddressByRole(array $role): array
    {
        $filter = ['role' => ['$in' => $role]];
        $cursor = $this->db->getTrackerCollection()->find($filter);

        $nodeList = [];
        foreach ($cursor as $item) {
            $nodeList[] = $item->address;
        }

        return $nodeList;
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
}
