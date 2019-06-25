<?php

namespace Saseul\System;

class HttpResponse
{
    private $code;
    private $data;
    private $html;
    private $header = ['Content-Type' => 'application/json; charset=utf-8;'];

    public function __construct($code, $data = [], $html = '')
    {
        $this->code = $code;
        $this->data = $data;
        $this->html = $html;

        if (!empty($html)) {
            $this->header['Content-Type'] = 'text/html; charset=utf-8;';
        }
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getData()
    {
        return $this->data;
    }

    public function appendToHeader($key, $value): void
    {
        $this->header[$key] = $value;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function isHtmlBody(): bool
    {
        return !empty($this->html);
    }

    public function getHtmlBody()
    {
        if (empty($this->data['data'])) {
            return $this->html;
        }
        $data = $this->data['data'];

        $wrap = function ($key) {
            return '{{' . $key . '}}';
        };

        $find = array_map($wrap, array_keys($data));
        $replace = array_values($data);

        return str_ireplace($find, $replace, $this->html);
    }
}
