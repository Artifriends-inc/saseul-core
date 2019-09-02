<?php

namespace Saseul\Api;

use Saseul\Common\Api;

class Request extends Api
{
    private $request;
    private $public_key;
    private $signature;
    private $apiName;
    private $api;

    public function _init()
    {
        $this->request = json_decode($this->getParam($_REQUEST, 'request', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
    }

    public function _process()
    {
        $this->existApi();
        $this->createApiInstance();
        $this->initialize();
        $this->validate();
    }

    public function _end(): void
    {
        $this->data = $this->api->getResponse();
    }

    private function existApi(): void
    {
        $type = $this->getParam($this->request, 'type');
        $this->apiName = 'Saseul\\Custom\\Request\\' . $type;
        if (class_exists($this->apiName) === false) {
            $this->error('Invalid Request');
        }
    }

    private function createApiInstance(): void
    {
        $this->api = (
            new \ReflectionClass($this->apiName)
        )->newInstance();
    }

    private function initialize(): void
    {
        $thash = hash('sha256', json_encode($this->request));
        $this->api->initialize(
            $this->request,
            $thash,
            $this->public_key,
            $this->signature
        );
    }

    private function validate(): void
    {
        if ($this->api->getValidity() === false) {
            $this->error('Invalid Request');
        }
    }
}
