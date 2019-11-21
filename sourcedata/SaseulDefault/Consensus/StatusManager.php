<?php

namespace Saseul\Consensus;

use Saseul\Core\ScriptFinder;

class StatusManager
{
    private $status_interfaces;

    public function __construct()
    {
        $this->status_interfaces = ScriptFinder::GetStatusInterfaces();
    }

    public function Reset()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_reset();
        }
    }

    public function Preprocess()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_preprocess();
        }
    }

    public function Load()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_load();
        }
    }

    /**
     * CommitManager 에서 Status 값을 저장한다.
     */
    public function Save()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_save();
        }
    }

    public function Postprocess()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_postprocess();
        }
    }
}
