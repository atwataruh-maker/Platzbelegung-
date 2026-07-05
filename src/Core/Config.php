<?php

namespace App\Core;

class Config
{
    private static ?array $data = null;

    public static function load(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = dirname(__DIR__, 2) . '/config/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            die(
                'Konfiguration fehlt: bitte config/config.example.php nach ' .
                'config/config.php kopieren und mit den echten Zugangsdaten fuellen.'
            );
        }

        self::$data = require $path;
        return self::$data;
    }

    public static function get(string $dotted, mixed $default = null): mixed
    {
        $data = self::load();
        $segments = explode('.', $dotted);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
