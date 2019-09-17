<?php

namespace Saseul\Common;

use ReflectionClass;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;

class ExternalApi
{
    protected $handlerData;
    protected $public_key;
    protected $signature;
    protected $apiName;
    protected $api;
    protected $result;
    private $handler;
    private $type;

    // TODO: external api가 모두 적용되면 이름을 run 으로 변경
    public function main(HttpRequest $httpRequest): HttpResponse
    {
        $this->handler = $httpRequest->getHandler();
        if ($this->configure()) {
            $this->handle();
        }

        return new HttpResponse(
            $this->result['code'],
            $this->result['data']
        );
    }

    protected function handle(): void
    {
    }

    protected function assembleResult($status, $data = []): void
    {
        $this->result['code'] = $status;
        $this->result['data'] = $data;
    }

    private function configure(): bool
    {
        if ($this->configureParameters() === false) {
            $this->assembleResult(HttpStatus::BAD_REQUEST);

            return false;
        }

        if ($this->configureApi() === false) {
            $this->assembleResult(HttpStatus::NOT_FOUND);

            return false;
        }

        return true;
    }

    private function configureParameters(): bool
    {
        // TODO: 아래의 변수 이름을 조금 더 명확하게 할 것
        $handlerJsonData = $this->getParam($_REQUEST, $this->handler, ['default' => '{}']);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
        $this->handlerData = json_decode($handlerJsonData, true);

        if ($this->handlerData === null) {
            return false;
        }

        $this->type = $this->getParam($this->handlerData, 'type');

        return
            $this->public_key !== null &&
            $this->signature !== null &&
            $this->type !== null
        ;
    }

    private function getParam(array $request, string $key, array $options = [])
    {
        if (!isset($request[$key]) && !isset($options['default'])) {
            return null;
        }

        $param = $request[$key] ?? $options['default'];

        if (isset($options['type']) && !$this->checkType($param, $options['type'])) {
            return null;
        }

        return $param;
    }

    private function checkType($param, string $type): bool
    {
        if (($type === 'string') && !is_string($param)) {
            return false;
        }

        if (($type === 'numeric') && !is_numeric($param)) {
            return false;
        }

        return true;
    }

    private function configureApi(): bool
    {
        if ($this->existApi()) {
            $this->createApiInstance();

            return true;
        }

        return false;
    }

    private function existApi(): bool
    {
        $handler = ucfirst($this->handler);
        $this->apiName = "Saseul\\Custom\\{$handler}\\{$this->type}";

        return class_exists($this->apiName);
    }

    private function createApiInstance(): void
    {
        $this->api = (new ReflectionClass($this->apiName))->newInstance();
    }
}
