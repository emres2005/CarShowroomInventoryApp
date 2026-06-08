<?php
/**
 * Simple PSR-4 style autoloader for the App namespace.
 */
spl_autoload_register(function ($class) {
    // We only handle the App\ namespace
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    // The base directory for the App namespace is the App/ directory itself.
    // Since this file is located in the App/ directory, __DIR__ is the base directory.
    $base_dir = __DIR__ . '/';
    
    // Get the relative class name (remove the App\ prefix)
    $relative_class = substr($class, 4);

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
