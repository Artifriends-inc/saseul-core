<?php

namespace Saseul\Common;

use Saseul\Util\Logger;

class Script
{
    protected $arg = [];
    protected $data = [];

    public function setArg($arg = [])
    {
        $this->arg = $arg;
    }

    public function main()
    {
        $this->exec();
        $this->result();
    }

    public function exec()
    {
        $this->_awake();
        $this->_process();
        $this->_end();
    }

    public function _awake()
    {
    }

    public function _process()
    {
    }

    public function _end()
    {
    }

    public function ask(string $msg): string
    {
        Logger::EchoLog(PHP_EOL . $msg);

        return trim(fgets(STDIN));
    }

    public function error($msg = 'Error')
    {
        Logger::Log('Error : ');
        Logger::Log($msg, true);
    }

    protected function result()
    {
        if ($this->data !== []) {
            Logger::Log($this->data, true);
        }
    }
}
