<?php
//This file is used to autoload classes from specified directories and instaniate them.

spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/Abstract/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/Mail/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
// Create an instance of the class

$ObjLayout = new Layouts();
$ObjForm = new Forms();
