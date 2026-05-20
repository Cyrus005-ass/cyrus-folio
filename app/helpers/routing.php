<?php

if (!function_exists('current_uri')) {
    function current_uri(): string
    {
        if (isset($_GET['url'])) {
            return '/' . trim((string) $_GET['url'], '/');
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = parse_url((string) env('APP_URL', ''), PHP_URL_PATH);
        if (is_string($basePath) && $basePath !== '' && $basePath !== '/' && str_starts_with($requestPath, $basePath)) {
            $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
        }

        if ($requestPath === '/public' || str_starts_with($requestPath, '/public/')) {
            $requestPath = substr($requestPath, strlen('/public')) ?: '/';
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim($scriptDir, '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($requestPath, $scriptDir)) {
            $requestPath = substr($requestPath, strlen($scriptDir));
        }
        return '/' . trim($requestPath, '/');
    }
}

if (!function_exists('request_method')) {
    function request_method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'HEAD') {
            return 'GET';
        }
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }
}
