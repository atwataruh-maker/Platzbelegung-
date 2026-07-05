<?php

namespace App\Core;

class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $handler = $this->match($method, $path);

        if ($handler === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        [$callable, $params] = $handler;

        if (is_array($callable)) {
            [$class, $action] = $callable;
            $controller = new $class();
            $controller->$action(...$params);
            return;
        }

        $callable(...$params);
    }

    /**
     * @return array{0: callable|array, 1: array<string,string>}|null
     */
    private function match(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$handler, array_values($params)];
            }
        }
        return null;
    }
}
