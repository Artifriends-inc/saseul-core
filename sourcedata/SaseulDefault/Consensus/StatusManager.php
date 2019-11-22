<?php

namespace Saseul\Consensus;

use Saseul\Core\ScriptFinder;

/**
 * Class StatusManager.
 *
 * Commit 시 Status 값들을 관리한다.
 */
class StatusManager
{
    private $status_interfaces;

    public function __construct()
    {
        $this->status_interfaces = ScriptFinder::GetStatusInterfaces();
    }

    /**
     * Status 값을 초기화한다.
     */
    public function reset(): void
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_reset();
        }
    }

    /**
     * Status 값을 불러온다.
     */
    public function load(): void
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_load();
        }
    }

    /**
     * Status 값을 전처리한다.
     */
    public function preprocess(): void
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_preprocess();
        }
    }

    /**
     * Status 값을 저장한다.
     */
    public function save(): void
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_save();
        }
    }

    /**
     * Status 값을 후처리한다.
     */
    public function postprocess(): void
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_postprocess();
        }
    }
}
