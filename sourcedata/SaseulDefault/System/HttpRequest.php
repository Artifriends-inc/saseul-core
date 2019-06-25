<?php

namespace Saseul\System;

class HttpRequest
{
    public const INVALID_URI = '10b29eee211212ca1d47207a2844c7a61c9dd239571e7455ace56a15b40274ab';
    private $uri;
    private $handler;
    private $request;
    private $server;
    private $getParams;
    private $postParams;

    public function __construct($request = [], $server = [], $getParams = [], $postParams = [])
    {
        $this->request = $request;
        $this->server = $server;
        $this->handler = ltrim(parse_url($this->getUri())['path'], '/');
        $this->getParams = $getParams;
        $this->postParams = $postParams;
    }

    public function getUri()
    {
        if (!empty($this->request['handler'])) {
            return $this->request['handler'];
        }
        if (!empty($this->server['REQUEST_URI'])) {
            return $this->server['REQUEST_URI'];
        }

        return self::INVALID_URI;
    }

    public function hasParam(string $key): bool
    {
        return isset($this->request[$key]);
    }

    public function getParam(string $key, $default)
    {
        if ($this->hasParam($key)) {
            return $this->request[$key];
        }

        return $default;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function getGetParams()
    {
        return $this->getParams;
    }

    public function getPostParams()
    {
        return $this->postParams;
    }

    public function getHttpMethod()
    {
        return $this->server['REQUEST_METHOD'] ?? HttpMethod::NO_METHOD;
    }

    public function setHttpMethod(string $method)
    {
        $this->server['REQUEST_METHOD'] = $method;
    }

    public function getRequest(): array
    {
        return $this->request;
    }
}
