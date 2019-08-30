<?php

namespace Saseul\Common;

use Saseul\System\HttpRequest;
use Saseul\System\Terminator;

class Api
{
    public $data = [];
    protected $display_params = true;
    protected $result = [];
    protected $httpRequest;

    public function main(HttpRequest $request = null): void
    {
        if ($request == null) {
            $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);
        }
        $this->httpRequest = $request;

        $this->_init();
        $this->_process();
        $this->_end();

        $this->success();
    }

    /**
     * Use $type to check the type of $param data.
     *
     * @param        $param
     * @param string $type
     *
     * @return bool
     */
    public static function checkType($param, string $type): bool
    {
        if (($type === 'string') && !is_string($param)) {
            return false;
        }

        if (($type === 'numeric') && !is_numeric($param)) {
            return false;
        }

        return true;
    }

    protected function _init()
    {
        // inherit
    }

    protected function _process()
    {
        // inherit
    }

    protected function _end()
    {
        // inherit
    }

    protected function success()
    {
        $status = 'success';
        $this->result['status'] = $status;
        $this->result['data'] = $this->data;

        if ($this->display_params === true) {
            $this->result['params'] = $_REQUEST;
        }

        $this->view($status);
    }

    protected function fail($code, $msg = '')
    {
        $status = 'fail';
        $this->result['status'] = $status;
        $this->result['code'] = $code;
        $this->result['msg'] = $msg;

        $this->view($status);
    }

    protected function error(string $msg = 'Error', $code = 999)
    {
        $this->fail($code, $msg);
    }

    protected function view($status)
    {
        try {
            header('Content-Type: application/json; charset=utf-8;');
        } catch (\Exception $e) {
            echo $e . PHP_EOL . PHP_EOL;
        }
        echo json_encode($this->result);

        Terminator::exit($status);
    }

    /**
     * Get request parameter.
     * If not set request parameter, default parameter data is set.
     *
     * @param array  $request
     * @param string $key
     * @param array  $options keys [default, type]
     *
     * @return float|int|string
     */
    protected function getParam(array $request, string $key, array $options = [])
    {
        if (!isset($request[$key]) && !isset($options['default'])) {
            $this->Error("There is no parameter: {$key}");
        }

        $param = $request[$key] ?? $options['default'];

        if (isset($options['type']) && !static::checkType($param, $options['type'])) {
            $this->Error("Wrong parameter type: {$key}");
        }

        return $param;
    }
}
