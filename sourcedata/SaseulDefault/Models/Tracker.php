<?php
namespace Saseul\Models;

class Tracker
{
    public $host;
    public $address;
    public $role;
    public $status;

    public function __construct($host, $address, $role, $status)
    {
        $this->host = $host;
        $this->address = $address;
        $this->role = $role;
        $this->status = $status;
    }
}
