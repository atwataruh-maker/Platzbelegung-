<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $viewPath = dirname(__DIR__) . '/views/' . $template . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            die("View nicht gefunden: {$template}");
        }

        $content = self::capture($viewPath, $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutPath = dirname(__DIR__) . '/views/' . $layout . '.php';
        $layoutData = $data;
        $layoutData['content'] = $content;
        echo self::capture($layoutPath, $layoutData);
    }

    private static function capture(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
