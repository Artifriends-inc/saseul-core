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
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') > -1) {
            $_POST = json_decode(file_get_contents('php://input'), true);
            $_REQUEST = empty($_POST) ? $_REQUEST : array_merge($_REQUEST, $_POST);
        }
    }

    /*
     * 요청에 대한 handler(request or transaction, etc..) 를 찾아 실행 시킨다.
     * handler 가 존재하면 인스턴스화하여 해당 handler 를 실행시킨다.
     * 존재하지 않을 경우 NotFound(400)을 반환한다.
     */
    public function run(): HttpResponse
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

        return $response;
    }

    /*
     * HTTP 요청에 대한 처리가 완료되면 만들어진 HttpResponse 를 통해 응답 헤더 및 data 를
     * 만들고 스크립트를 종료한다.
     */
    public function finish(HttpResponse $resp): void
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
        return (new ReflectionClass($handlerPath))->newInstance();
    }
}
