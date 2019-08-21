<?php

namespace Saseul\Api;

use Saseul\Common\Api;

// TODO: 단위 테스트 코드 필요 (2019. 08. 21 수동 검사 완료)

class Request extends Api
{
    private $request;
    private $public_key;
    private $signature;
    private $requestedApi;

    public function _init()
    {
        $this->request = json_decode($this->getParam($_REQUEST, 'request', ['default' => '{}']), true);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
    }

    public function _process()
    {
        $className = $this->assembleRequestClassName();
        $this->existRequestedApi($className);
        $this->createRequestedApi($className);
        $this->initializeRequestedApi();
        $this->checkRequestValidation();
    }

    public function _end(): void
    {
        $this->data = $this->requestedApi->getResponse();
    }

    private function assembleRequestClassName(): string
    {
        $type = $this->getParam($this->request, 'type');

        return 'Saseul\\Custom\\Request\\' . $type;
    }

    private function existRequestedApi($className): void
    {
        if (class_exists($className) === false) {
            $this->error('Invalid Request');
        }
    }

    private function createRequestedApi($className): void
    {
        $this->requestedApi = (new \ReflectionClass($className))->newInstance();
    }

    private function initializeRequestedApi(): void
    {
        $thash = hash('sha256', json_encode($this->request));
        $this->requestedApi->initialize(
            $this->request,
            $thash,
            $this->public_key,
            $this->signature
        );
    }

    private function checkRequestValidation(): void
    {
        if ($this->requestedApi->getValidity() === false) {
            $this->error('Invalid Request');
        }
    }
}
