<?php

namespace Saseul\Consensus;

use Saseul\Constant\Structure;
use Saseul\Core\Property;
use Saseul\Core\Tracker;
use Saseul\Custom\Method\Attributes;
use Saseul\System\Cache;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class TrackerManager
{
    private static $instance = null;

    private $cache;
    private $rest;
    private $logger;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
        $this->rest = RestCall::GetInstance();
        $this->logger = Logger::getLogger('Daemon');
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @todo 해당 부분은 조건을 변경해주어야 한다.
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function GenerateTracker()
    {
        $validators = Attributes::GetValidator();
        $supervisors = Attributes::GetSupervisor();
        $arbiters = Attributes::GetArbiter();
        $fullnodes = Attributes::GetFullNode();
        $this->logger->debug(
            'nodes',
            [
                'validators' => $validators,
                'supervisors' => $supervisors,
                'arbiters' => $arbiters,
                'fullnodes' => $fullnodes
            ]
        );

        $validators_in_tracker = Tracker::GetValidatorAddress();
        $supervisors_in_tracker = Tracker::GetSupervisorAddress();
        $arbiters_in_tracker = Tracker::GetArbiterAddress();
        $fullnodes_in_tracker = Tracker::GetFullNodeAddress();
        $this->logger->debug(
            'in_tracker',
            [
                'validator' => $validators_in_tracker,
                'supervisor' => $supervisors_in_tracker,
                'arbiters' => $arbiters_in_tracker,
                'fullnodes' => $fullnodes_in_tracker
            ]
        );

        foreach ($validators as $validator) {
            if (!in_array($validator, $validators_in_tracker, true)) {
                Tracker::SetValidator($validator);
            }
        }

        foreach ($supervisors as $supervisor) {
            if (!in_array($supervisor, $supervisors_in_tracker, true)) {
                Tracker::SetSupervisor($supervisor);
            }
        }

        foreach ($arbiters as $arbiter) {
            if (!in_array($arbiter, $arbiters_in_tracker, true)) {
                Tracker::SetArbiter($arbiter);
            }
        }

        // Todo: 해당 부분을 주석 처리해야 Validator 노드가 Light 노드로 변환되지 않는다.
        foreach ($fullnodes_in_tracker as $fullnode) {
            if (!in_array($fullnode, $fullnodes, true)) {
                Tracker::SetLightNode($fullnode);
            }
        }
    }

    public function collect($aliveNodes, $alives)
    {
        // TODO: last_block 다르면 fork 처리 해야함.
        $infos = [];
        $hosts = [];

        foreach ($aliveNodes as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'nodes');

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            // check result;
            if (!isset($r['data']) || !is_array($r['data'])) {
                continue;
            }

            foreach ($r['data'] as $node) {
                // check structure;
                if (TypeChecker::StructureCheck(Structure::TRACKER, $node) === false) {
                    continue;
                }

                // target;
                if ($node['host'] === $item['host']) {
                    $infos[$node['host']] = [
                        'address' => $node['address'],
                        'host' => $node['host'],
                    ];
                }

                // etc;
                if (!isset($infos[$node['host']]) && $node['address'] !== '') {
                    $infos[$node['host']] = [
                        'address' => $node['address'],
                        'host' => $node['host'],
                    ];
                }
            }
        }

        Tracker::registerRequest(array_values($infos));
    }

    public function register($nodes, $alives)
    {
        $infos = [];

        // die;
        foreach ($nodes as $node) {
            if (in_array($node['address'], $alives)) {
                $infos[$node['host']] = [
                    'address' => $node['address'],
                    'host' => $node['host'],
                ];
            } else {
                $infos[$node['host']] = [
                    'address' => $node['address'],
                    'host' => '',
                ];
            }
        }

        Property::registerRequest([]);

        if (count($infos) > 0) {
            Tracker::setHosts($infos);
        }
    }
}
