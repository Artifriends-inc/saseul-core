<?php

namespace Saseul\Common;

use Saseul\Core\Service;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;
use Saseul\System\Terminator;

class ApiLoader
{
    public function __construct()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') > -1) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            $_REQUEST = empty($_POST) ? $_REQUEST : array_merge($_REQUEST, $_POST);
        }

        Service::initApi();
    }

    public function main(): void
    {
        $req = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);
        $resp = $this->route($req);

        $this->response($resp);
    }

    private function route(HttpRequest $req): HttpResponse
    {
        $uri = $req->getUri();

        if ($uri === '/') {
            $uri = '/main';
        }

        if ($uri === HttpRequest::INVALID_URI) {
            return new HttpResponse(HttpStatus::NOT_FOUND);
        }

        $apiName = ltrim(parse_url($uri)['path'], '/');
        $apiClassName = $this->getApiClassName($apiName);

        if ($apiClassName === '') {
            return new HttpResponse(HttpStatus::NOT_FOUND);
        }

        if (!class_exists($apiClassName)) {
            return new HttpResponse(HttpStatus::NOT_FOUND);
        }

        $api = new $apiClassName();
        $api->main($req);

        return new HttpResponse($api->getResult()['code'], $api->getResult(), $api->getHtml());
    }

    private function response(HttpResponse $resp): void
    {
        http_response_code($resp->getCode());
        if (!headers_sent()) {
            $header = $resp->getHeader();

            foreach ($header as $key => $value) {
                header("${key}: ${value}");
            }
        }

        if ($resp->isHtmlBody()) {
            echo $resp->getHtmlBody();
        } else {
            echo json_encode($resp->getData());
        }

        Terminator::exit();
    }

    private function getApiClassName(string $apiName) : string
    {
        $parent = ROOT_DIR . '/Saseul/Api';
        $target = $apiName;

        if (!preg_match('/\.php$/', $target)) {
            $target = "{$target}.php";
        }

        $dir = explode('/' , $target);
        $apiClassName = '';

        foreach ($dir as $item) {
            $child = $this->getChildName($parent, $item);

            if ($child === '') {
                return '';
            }

            $parent = "{$parent}/{$child}";
            $apiClassName = "{$apiClassName}/{$child}";
        }

        $apiClassName = preg_replace('/\.php$/', '', $apiClassName);
        $apiClassName = preg_replace('/\\/{1,}/', '\\', $apiClassName);
        $apiClassName = "Saseul\\Api{$apiClassName}";

        return $apiClassName;
    }

    private function getChildName(string $parent, string $child) : string
    {
        if (is_dir($parent)) {
            $dir = scandir($parent);

            foreach ($dir as $item) {
                if (strtolower($child) === strtolower($item)) {
                    return $item;
                }
            }
        }

        return '';
    }
}