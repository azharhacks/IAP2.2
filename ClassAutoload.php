<?php
/**
 * Class Autoloader
 * Automatically loads PHP classes from specified directories
 * Supports both exact case and lowercase filename matching for flexibility
 */

// Register autoloader for Abstract classes directory
spl_autoload_register(function ($class_name) {
    // First attempt: try exact case filename match
    $file = __DIR__ . '/Abstract/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Second attempt: try lowercase filename match
    // This handles cases where class names use different casing conventions
    $file = __DIR__ . '/Abstract/' . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

// Register autoloader for Mail classes directory
spl_autoload_register(function ($class_name) {
    // First attempt: try exact case filename match
    $file = __DIR__ . '/Mail/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Second attempt: try lowercase filename match
    // This handles cases where class names use different casing conventions
    $file = __DIR__ . '/Mail/' . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});
?>
