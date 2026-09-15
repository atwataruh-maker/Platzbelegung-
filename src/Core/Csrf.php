<?php

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify(Request::input('csrf_token'))) {
            http_response_code(419);
            die('Sicherheitspruefung fehlgeschlagen (CSRF-Token ungueltig). Bitte Seite neu laden und erneut versuchen.');
        }
    }
}
