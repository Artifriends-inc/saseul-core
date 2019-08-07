<?php

namespace Saseul\Common;

use Saseul\Core\ScriptFinder;
use Saseul\Core\Service;

class ScriptLoader
{
    private $argv;
    private $script_dir;

    public function __construct($argv)
    {
        $this->argv = $argv;
        $this->script_dir = ROOT_DIR . '/Saseul/Script';

        if (!isset($this->argv[1])) {
            $this->argv[1] = '';
        }

        Service::initScript();
    }

    public function main(): void
    {
        $script = $this->route($this->argv[1]);

        if ($script === '') {
            $this->ShowAllScripts();
        } else {
            $this->ExecScript($script);
        }
    }

    public function route(string $arg): string
    {
        $script_file = "{$this->script_dir}/{$arg}";

        if (!preg_match('/\.php$/', $script_file)) {
            $script_file = $script_file . '.php';
        }

        if (is_file($script_file)) {
            return $arg;
        }

        return '';
    }

    public function showAllScripts()
    {
        echo PHP_EOL;
        echo 'You can run like this ' . PHP_EOL;
        echo ' $ saseul_script <script_name>';
        echo PHP_EOL;
        echo PHP_EOL;
        echo 'This is script lists. ' . PHP_EOL;

        $scripts = ScriptFinder::GetFiles($this->script_dir, $this->script_dir);
        $scripts = preg_replace('/\.php$/', '', $scripts);

        foreach ($scripts as $script) {
            echo ' - ' . $script . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function execScript($script)
    {
        $script = 'Saseul/Script/' . $script;
        $script = preg_replace('/\.php$/', '', $script);
        $script = preg_replace('/\//', '\\', $script);

        $arg = [];

        if (count($this->argv) > 2) {
            $arg = $this->argv;
            unset($arg[0], $arg[1]);

            $arg = array_values($arg);
        }

        $target = new $script();
        $target->setArg($arg);
        $target->main();
    }
}
