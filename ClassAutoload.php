<?php
/**
 * Class Autoloader for E-commerce System
 * Automatically loads classes when they are first used
 */

spl_autoload_register(function ($class_name) {
    // Define class directories
    $directories = [
        __DIR__ . '/Abstract/',
        __DIR__ . '/Mail/',
        __DIR__ . '/classes/',
        __DIR__ . '/'
    ];
    
    // Try to find and include the class file
    foreach ($directories as $directory) {
        // Try the exact case first
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        
        // Try lowercase for case-insensitive filesystems
        $file = $directory . strtolower($class_name) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>
