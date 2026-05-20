<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable|array $handler): void { $this->add('POST', $pattern, $handler); }
    public function put(string $pattern, callable|array $handler): void { $this->add('PUT', $pattern, $handler); }
    public function delete(string $pattern, callable|array $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern === '/') {
            $pattern = '/';
        }
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        if ($uri === '//') {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            [$regex, $params] = $this->compile($route['pattern']);
            if (preg_match($regex, $uri, $matches)) {
                $args = [];
                foreach ($params as $param) {
                    $args[] = $matches[$param] ?? null;
                }
                return $this->call($route['handler'], $args);
            }
        }

        http_response_code(404);
        if (str_starts_with($uri, '/api/')) {
            json_response(['success' => false, 'message' => 'Route API introuvable'], 404);
        }
        return view('errors/404', ['uri' => $uri], 'public');
    }

    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);
        return ['#^' . $regex . '$#', $params];
    }

    private function call(callable|array $handler, array $args): mixed
    {
        if (is_array($handler) && is_string($handler[0])) {
            $class = $handler[0];
            $method = $handler[1];
            $instance = new $class();
            return $instance->{$method}(...$args);
        }
        return $handler(...$args);
    }
}
