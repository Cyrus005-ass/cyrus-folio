<?php

use App\Core\Env;
use App\Services\AuthService;
use App\Services\SchemaService;

if (!headers_sent()) {
    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || $forwardedProto === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $cspDirectives = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "script-src 'self' 'unsafe-inline' https://www.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com data:",
        "img-src 'self' data: https:",
        "media-src 'self' data: blob: https:",
        "connect-src 'self' https:",
        "frame-src 'self' https:",
    ];

    header_remove('X-Powered-By');
    header('Content-Security-Policy: ' . implode('; ', $cspDirectives));
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Origin-Agent-Cluster: ?1');

    if ($secureCookie) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('RESOURCE_PATH', BASE_PATH . '/resources');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once APP_PATH . '/Core/Env.php';
Env::load(BASE_PATH . '/.env');

$runtimeHostHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$runtimeHost = strtolower(trim((string) explode(',', $runtimeHostHeader)[0]));
if (str_starts_with($runtimeHost, '[') && str_contains($runtimeHost, ']')) {
    $runtimeHost = substr($runtimeHost, 1, max(0, strpos($runtimeHost, ']') - 1));
} else {
    $runtimeHost = preg_replace('/:\d+$/', '', $runtimeHost) ?? $runtimeHost;
}

$isLocalRuntime = PHP_SAPI === 'cli'
    || in_array($runtimeHost, ['localhost', '127.0.0.1', '::1', 'host.docker.internal'], true)
    || (filter_var($runtimeHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
        && preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $runtimeHost) === 1)
    || str_ends_with($runtimeHost, '.local')
    || str_ends_with($runtimeHost, '.test')
    || str_ends_with($runtimeHost, '.localhost')
    || str_ends_with($runtimeHost, '.internal');

if ($isLocalRuntime) {
    // Allow local DB and URL overrides without editing the shared .env.
    Env::load(BASE_PATH . '/.env.local');
}

$appConfigFile = CONFIG_PATH . '/app.php';
define('APP_CONFIG', is_file($appConfigFile) ? require $appConfigFile : []);

$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

foreach (glob(APP_PATH . '/helpers/*.php') as $helper) {
    require_once $helper;
}

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');

try {
    SchemaService::ensureLatest();
    AuthService::ensureDefaultAdmin();
    AuthService::restoreRememberedUser();
} catch (Throwable $e) {
    // La base n'est peut-etre pas encore importee. La page d'installation/README guide l'utilisateur.
}

