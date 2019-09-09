<?php

namespace Saseul\Common;

use ReflectionClass;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;

class ExternalApi
{
    protected $request;
    protected $public_key;
    protected $signature;
    protected $apiName;
    protected $api;
    protected $result;
    private $type;

    // TODO: external api가 모두 적용되면 이름을 run 으로 변경
    public function main(): HttpResponse
    {
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

    protected function assembleResult($status, $data = ''): void
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
        $requestJson = $this->getParam($_REQUEST, 'request', ['default' => '{}']);
        $this->public_key = $this->getParam($_REQUEST, 'public_key', ['default' => '']);
        $this->signature = $this->getParam($_REQUEST, 'signature', ['default' => '']);
        $this->request = json_decode($requestJson, true);

        if ($this->request === null) {
            return false;
        }

        $this->type = $this->getParam($this->request, 'type');

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

        if (isset($options['type']) && !static::checkType($param, $options['type'])) {
            return null;
        }

        return $param;
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
        $this->apiName = 'Saseul\\Custom\\Request\\' . $this->type;

        return class_exists($this->apiName);
    }

    private function createApiInstance(): void
    {
        $this->api = (new ReflectionClass($this->apiName))->newInstance();
    }
}
