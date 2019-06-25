<?php

namespace Saseul\Consensus;

use Saseul\Constant\Structure;
use Saseul\Core\Tracker;
use Saseul\Custom\Method\Attributes;
use Saseul\System\Cache;
use Saseul\Util\RestCall;
use Saseul\Util\TypeChecker;

class TrackerManager
{
    private static $instance = null;

    private $cache;
    private $rest;

    public function __construct()
    {
        $this->cache = Cache::GetInstance();
        $this->rest = RestCall::GetInstance();
    }

    public static function GetInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function GenerateTracker()
    {
        $validators = Attributes::GetValidator();
        $supervisors = Attributes::GetSupervisor();
        $arbiters = Attributes::GetArbiter();
        $fullnodes = Attributes::GetFullNode();

        $validators_in_tracker = Tracker::GetValidatorAddress();
        $supervisors_in_tracker = Tracker::GetSupervisorAddress();
        $arbiters_in_tracker = Tracker::GetArbiterAddress();
        $fullnodes_in_tracker = Tracker::GetFullNodeAddress();

        foreach ($validators as $validator) {
            if (!in_array($validator, $validators_in_tracker)) {
                Tracker::SetValidator($validator);
            }
        }

        foreach ($supervisors as $supervisor) {
            if (!in_array($supervisor, $supervisors_in_tracker)) {
                Tracker::SetSupervisor($supervisor);
            }
        }

        foreach ($arbiters as $arbiter) {
            if (!in_array($arbiter, $arbiters_in_tracker)) {
                Tracker::SetArbiter($arbiter);
            }
        }

        foreach ($fullnodes_in_tracker as $fullnode) {
            if (!in_array($fullnode, $fullnodes)) {
                Tracker::SetLightNode($fullnode);
            }
        }
    }

    public function collect($aliveNodes, $alives) {
        # TODO: last_block 다르면 fork 처리 해야함.
        $infos = [];
        $hosts = [];

        foreach ($aliveNodes as $node) {
            $hosts[] = $node['host'];
        }

        $results = $this->rest->MultiPOST($hosts, 'nodes');

        foreach ($results as $item) {
            $r = json_decode($item['result'], true);

            # check result;
            if (!isset($r['data']) || !is_array($r['data'])) {
                continue;
            }

            foreach ($r['data'] as $node) {
                # check structure;
                if (TypeChecker::StructureCheck(Structure::TRACKER, $node) === false) {
                    continue;
                }

                # target;
                if ($node['host'] === $item['host']) {
                    $infos[$node['host']] = [
                        'address' => $node['address'],
                        'host' => $node['host'],
                    ];
                }

                # etc;
                if (!isset($infos[$node['host']]) && $node['address'] !== '') {
                    $infos[$node['host']] = [
                        'address' => $node['address'],
                        'host' => $node['host'],
                    ];
                }
            }
        }
    }

    public function register($nodes, $alives)
    {
        # die;
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

        Tracker::setHosts($infos);
    }
}
