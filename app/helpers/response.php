<?php

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('is_api_request')) {
    function is_api_request(): bool
    {
        return str_starts_with('/' . trim(current_uri(), '/'), '/api/');
    }
}
