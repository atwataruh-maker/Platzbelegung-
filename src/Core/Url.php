<?php

namespace App\Core;

class Url
{
    public static function to(string $path = '/'): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $scriptDir . $path;
    }
}
