<?php

namespace Saseul\Common;

use ReflectionClass;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;
use Saseul\System\Terminator;

class HandlerLoader
{
    public function __construct()
    {
        // TODO: POST, GET 요청 분리
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') > -1) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            $_REQUEST = empty($_POST) ? $_REQUEST : array_merge($_REQUEST, $_POST);
        }
    }

    // TODO: 단위 테스트 작성 및 if 문 리팩터링 필요
    public function run(): void
    {
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        $handlerName = $this->getHandlerName($request);
        $handlerPath = $this->getHandlerPath($handlerName);
        $response = null;
        if ($this->existsHandler($handlerPath)) {
            $handler = $this->instantiateHandler($handlerPath);
            $response = $handler->invoke($request);
        } else {
            $response = new HttpResponse(HttpStatus::NOT_FOUND);
        }

        $this->response($response);
    }

    private function getHandlerName(HttpRequest $request): string
    {
        switch ($request->getUri()) {
            case HttpRequest::INVALID_URI:
                return HttpRequest::INVALID_URI;
            case '/':
                return 'main';

                break;
            default:
                return $request->getHandler();

                break;
        }
    }

    // TODO: 반복문을 통해서 경로를 찾는게 맞는지 고려 후 제거 필요
    private function getHandlerPath(string $handlerName): string
    {
        $parent = ROOT_DIR . '/Saseul/Api';
        $target = $handlerName;

        if (!preg_match('/\.php$/', $target)) {
            $target = "{$target}.php";
        }

        $dir = explode('/', $target);
        $path = '';

        foreach ($dir as $item) {
            $child = $this->getChildName($parent, $item);

            if ($child === '') {
                return '';
            }

            $parent = "{$parent}/{$child}";
            $path = "{$path}/{$child}";
        }

        $pathWithoutFileExtension = $this->removeFileExtension($path);
        $handlerPath = $this->changeForwardSlashToBackSlash($pathWithoutFileExtension);

        return "Saseul\\Api{$handlerPath}";
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

    private function removeFileExtension(string $apiClassName)
    {
        return preg_replace('/\.php$/', '', $apiClassName);
    }

    private function changeForwardSlashToBackSlash($apiClassName)
    {
        return preg_replace('/\\/{1,}/', '\\', $apiClassName);
    }

    private function existsHandler(string $handlerPath): bool
    {
        return class_exists($handlerPath);
    }

    private function instantiateHandler($handlerPath)
    {
        return $this->api = (new ReflectionClass($handlerPath))->newInstance();
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

        Terminator::exit(0);
    }
}
