<?php

namespace Saseul\Api;

use Saseul\Common\Api;
use Saseul\Consensus\ResourceManager;

class Resource extends Api
{
    private $resourceManager;

    private $resource;
    private $public_key;
    private $signature;

    public function __construct()
    {
        $this->resourceManager = new ResourceManager();
    }

    public function _init(): void
    {
        $this->resource = json_decode($this->getParam($_REQUEST, 'resource', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
    }

    public function _process(): void
    {
        $type = $this->getParam($this->resource, 'type');
        $thash = hash('sha256', json_encode($this->resource));

        $this->resourceManager->initialize(
            $type,
            $this->resource,
            $thash,
            $this->public_key,
            $this->signature
        );
        $validity = $this->resourceManager->getValidity();

        if ($validity === false) {
            $this->Error('Invalid request');
        }

        $this->resourceManager->process();
    }

    public function _end(): void
    {
        $this->data = $this->resourceManager->getResponse();
    }
}
