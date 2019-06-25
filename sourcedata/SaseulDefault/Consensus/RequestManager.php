<?php

namespace Saseul\Consensus;

use Saseul\Common\Request;
use Saseul\Core\ScriptFinder;

class RequestManager
{
    private $request_interfaces;
    private $request;

    public function __construct()
    {
        $this->request_interfaces = [];
        $this->request = new Request();

        $request_interfaces = ScriptFinder::GetRequestInterfaces();
        $vrequest_interfaces = ScriptFinder::GetVRequestInterfaces();

        foreach ($request_interfaces as $request_interface) {
            $class = 'Saseul\\Custom\\Request\\' . $request_interface;
            $this->request_interfaces[$request_interface] = new $class();
        }

        foreach ($vrequest_interfaces as $vrequest_interface) {
            $class = 'Saseul\\Custom\\VRequest\\' . $vrequest_interface;
            $this->request_interfaces[$vrequest_interface] = new $class();
        }
    }

    public function initialize(): void
    {
        $this->request = null;
    }

    public function initializeRequest($type, $request, $thash, $public_key, $signature): void
    {
        if (isset($this->request_interfaces[$type])) {
            $this->request = $this->request_interfaces[$type];
        }

        $this->request->initialize($request, $thash, $public_key, $signature);
    }

    public function getRequestValidity(): bool
    {
        return $this->request->getValidity();
    }

    public function getResponse(): array
    {
        return $this->request->getResponse();
    }
}
