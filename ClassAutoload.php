<?php
//This file is used to autoload classes from specified directories and instantiate them.

spl_autoload_register(function ($class_name) {
    // Try the exact case first
    $file = __DIR__ . '/Abstract/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Try lowercase
    $file = __DIR__ . '/Abstract/' . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

spl_autoload_register(function ($class_name) {
    // Try the exact case first
    $file = __DIR__ . '/Mail/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Try lowercase
    $file = __DIR__ . '/Mail/' . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});
?>
