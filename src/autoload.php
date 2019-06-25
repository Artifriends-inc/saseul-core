<?php
spl_autoload_register(function ($class_name) {
    if (!class_exists($class_name) && preg_match('/^(\\\?Saseul)/', $class_name)) {
        $class = str_replace('\\', '/', $class_name);
        require_once __DIR__ . "/{$class}.php";
    }
});