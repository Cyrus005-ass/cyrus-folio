#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Services\SchemaService;
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('RESOURCE_PATH', BASE_PATH . '/resources');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once APP_PATH . '/Core/Env.php';
Env::load(BASE_PATH . '/.env');

if (is_file(BASE_PATH . '/.env.local')) {
    Env::load(BASE_PATH . '/.env.local');
}

$appConfigFile = CONFIG_PATH . '/app.php';
define('APP_CONFIG', is_file($appConfigFile) ? require $appConfigFile : []);

$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative) . '.php';
    $segments = explode('/', $relativePath);
    if ($segments !== []) {
        $segments[0] = match ($segments[0]) {
            'Controllers' => 'controllers',
            'Models' => 'models',
            'Services' => 'services',
            'Middleware' => 'middleware',
            default => $segments[0],
        };
    }

    $file = APP_PATH . '/' . implode('/', $segments);
    if (is_file($file)) {
        require_once $file;
    }
});

foreach (glob(APP_PATH . '/helpers/*.php') ?: [] as $helper) {
    require_once $helper;
}

$options = getopt('', ['name:', 'email:', 'password:', 'role:', 'help']);
if (isset($options['help'])) {
    printUsage();
    exit(0);
}

$name = trim((string) ($options['name'] ?? prompt('Admin name')));
$email = strtolower(trim((string) ($options['email'] ?? prompt('Admin email'))));
$password = (string) ($options['password'] ?? getenv('ADMIN_PASSWORD') ?: prompt('Admin password'));
$role = trim((string) ($options['role'] ?? 'super_admin'));

if ($name === '') {
    fail('Name is required.');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fail('A valid email is required.');
}

if (strlen($password) < 8) {
    fail('Password must contain at least 8 characters.');
}

if ($role === '') {
    $role = 'super_admin';
}

try {
    SchemaService::ensureLatest();

    $pdo = Database::connect();
    $pdo->beginTransaction();

    $existingUser = Database::query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $action = 'created';

    if ($existingUser) {
        $userId = (int) $existingUser['id'];
        Database::query(
            'UPDATE users SET name = ?, password = ?, role = ?, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$name, $passwordHash, $role, $userId]
        );
        $action = 'updated';
    } else {
        Database::query(
            'INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
            [$name, $email, $passwordHash, $role]
        );
        $userId = (int) Database::lastInsertId();
    }

    $profile = Database::query('SELECT id FROM profiles WHERE user_id = ?', [$userId])->fetch();
    if ($profile) {
        Database::query(
            'UPDATE profiles SET full_name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?',
            [$name, $email, $userId]
        );
    } else {
        Database::query(
            'INSERT INTO profiles (user_id, full_name, title, bio, email, location, github_url, linkedin_url, website_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $name, 'Fullstack Developer', 'Profil administrateur.', $email, 'Africa/Porto-Novo', '', '', '']
        );
    }

    $pdo->commit();

    fwrite(STDOUT, sprintf("Admin %s successfully.\nID: %d\nEmail: %s\nRole: %s\n", $action, $userId, $email, $role));
    exit(0);
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fail($e->getMessage());
}

function prompt(string $label): string
{
    fwrite(STDOUT, $label . ': ');
    $value = fgets(STDIN);
    return trim((string) $value);
}

function printUsage(): void
{
    $script = 'php scripts/create-admin.php';
    $lines = [
        'Create or update an admin account in the users table.',
        '',
        'Usage:',
        '  ' . $script . ' --name="Cyrus" --email="admin@example.com" --password="StrongPass123!" --role="super_admin"',
        '  ADMIN_PASSWORD="StrongPass123!" ' . $script . ' --name="Cyrus" --email="admin@example.com"',
        '',
        'Options:',
        '  --name       Admin display name',
        '  --email      Admin login email',
        '  --password   Admin password',
        '  --role       Role to store in DB (default: super_admin)',
        '  --help       Show this help',
    ];

    fwrite(STDOUT, implode(PHP_EOL, $lines) . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit(1);
}
