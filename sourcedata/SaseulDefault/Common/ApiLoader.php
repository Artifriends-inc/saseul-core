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
        // API 호출 시 최초로 호출되는 생성자로서 요청에 내용 확인 및 처리, 현재 POST로 모두 처리

        // TODO: POST, GET 요청 분리
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') > -1) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            $_REQUEST = empty($_POST) ? $_REQUEST : array_merge($_REQUEST, $_POST);
        }

        Service::initApi();
    }

    // TODO: 실제 기능과 메서드의 이름이 일치하지 않아 명확하지 않음, 메서드 이름 변경 필요
    public function main(): void
    {
        $req = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);
        $resp = $this->route($req);

        // 현재 아래 코드는 동작하지 않는다.
        $this->response($resp);
    }

    // TODO: route 기능 분리, 너무 많은 기능을 담당(api 호출 및 응답 생성 등..)
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

        // TODO: 실제 동작하지 않는 부분, 추가 작업 필요
        return new HttpResponse($api->getResult()['code'], $api->getResult(), $api->getHtml());
    }

    private function response(HttpResponse $resp): void
    {
        http_response_code($resp->getCode());
        if (!headers_sent()) {
            $header = $resp->getHeader();
            foreach ($header as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        if ($resp->isHtmlBody()) {
            echo $resp->getHtmlBody();
        } else {
            echo json_encode($resp->getData());
        }

        Terminator::exit();
    }

    private function getApiClassName(string $apiName): string
    {
        $parent = ROOT_DIR . '/Saseul/Api';
        $target = $apiName;

        if (!preg_match('/\.php$/', $target)) {
            $target = "{$target}.php";
        }

        $dir = explode('/', $target);
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

        return "Saseul\\Api{$apiClassName}";
    }

    private function getChildName(string $parent, string $child): string
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
