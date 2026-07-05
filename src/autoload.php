<?php
// Minimaler PSR-4-artiger Autoloader fuer den Namespace App\.
// Bewusst ohne Composer, damit die Anwendung ohne SSH-Zugang per FTP
// auf ein STRATO-Webhosting hochgeladen werden kann.

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
