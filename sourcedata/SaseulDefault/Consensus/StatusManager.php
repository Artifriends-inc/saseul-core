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
            $class::_Reset();
        }
    }

    public function Preprocess()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_Preprocess();
        }
    }

    public function Load()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_Load();
        }
    }

    public function Save()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_Save();
        }
    }

    public function Postprocess()
    {
        foreach ($this->status_interfaces as $status_interface) {
            $class = 'Saseul\\Custom\\Status\\' . $status_interface;
            $class::_Postprocess();
        }
    }
}
