<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Consensus\RequestManager;

class Request extends Api
{
    protected $request_manager;

    protected $request;
    protected $public_key;
    protected $signature;

    public function __construct()
    {
        $this->request_manager = new RequestManager();
    }

    public function _init()
    {
        $this->request = json_decode($this->getParam($_REQUEST, 'request', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
    }

    public function _process()
    {
        $type = $this->getParam($this->request, 'type');
        $request = $this->request;
        $thash = hash('sha256', json_encode($request));
        $public_key = $this->public_key;
        $signature = $this->signature;

        $this->request_manager->initializeRequest($type, $request, $thash, $public_key, $signature);
        $validity = $this->request_manager->getRequestValidity();

        if ($validity == false) {
            $this->Error('Invalid request');
        }
    }

    public function _end()
    {
        $this->data = $this->request_manager->getResponse();
    }
}
