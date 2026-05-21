<?php

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('request_bearer_token')) {
    function request_bearer_token(): ?string
    {
        $headers = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (is_array($apacheHeaders)) {
                $headers[] = $apacheHeaders['Authorization'] ?? $apacheHeaders['authorization'] ?? null;
            }
        }

        foreach ($headers as $header) {
            if (!is_string($header) || trim($header) === '') {
                continue;
            }

            if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) === 1) {
                $token = trim((string) ($matches[1] ?? ''));
                return $token !== '' ? $token : null;
            }
        }

        return null;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }
}
