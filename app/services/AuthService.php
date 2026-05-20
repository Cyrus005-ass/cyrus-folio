<?php

namespace App\Services;

use App\Core\Database;
use App\Models\RememberToken;
use App\Models\User;
use Throwable;

class AuthService
{
    private const REMEMBER_COOKIE = 'portfolio_os_remember';
    private const REMEMBER_TTL = 2592000;

    public static function login(string $email, string $password, bool $remember = false): bool
    {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password'])) {
            return false;
        }

        self::storeSession($user);
        $userModel->updateLastLogin((int) $user['id']);

        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        } else {
            self::forgetRememberedUser((int) $user['id']);
        }

        ActivityService::log('login', 'Connexion administrateur');
        return true;
    }

    public static function logout(): void
    {
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        ActivityService::log('logout', 'Deconnexion administrateur');
        self::forgetRememberedUser($userId > 0 ? $userId : null);
        self::destroySession();
    }

    public static function restoreRememberedUser(): void
    {
        if (auth_check()) {
            return;
        }

        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if ($cookie === '' || !str_contains($cookie, ':')) {
            return;
        }

        [$selector, $token] = array_pad(explode(':', $cookie, 2), 2, '');
        if ($selector === '' || $token === '') {
            self::clearRememberCookie();
            return;
        }

        $tokenModel = new RememberToken();
        $tokenModel->purgeExpired();
        $rememberToken = $tokenModel->findBySelector($selector);
        if (!$rememberToken || !hash_equals((string) $rememberToken['token_hash'], hash('sha256', $token)) || strtotime((string) $rememberToken['expires_at']) < time()) {
            if ($rememberToken) {
                $tokenModel->deleteBySelector($selector);
            }
            self::clearRememberCookie();
            return;
        }

        $user = (new User())->find((int) $rememberToken['user_id']);
        if (!$user || !(int) $user['is_active']) {
            $tokenModel->deleteBySelector($selector);
            self::clearRememberCookie();
            return;
        }

        self::storeSession($user);
        self::issueRememberToken((int) $user['id']);
    }

    public static function ensureDefaultAdmin(): void
    {
        $count = (int) Database::query('SELECT COUNT(*) total FROM users')->fetch()['total'];
        if ($count > 0) {
            $firstUser = Database::query('SELECT id, name, email FROM users ORDER BY id ASC LIMIT 1')->fetch();
            if ($firstUser) {
                $profileCount = (int) Database::query('SELECT COUNT(*) total FROM profiles WHERE user_id = ?', [$firstUser['id']])->fetch()['total'];
                if ($profileCount === 0) {
                    Database::query(
                        'INSERT INTO profiles (user_id, full_name, title, bio, email, location, github_url, linkedin_url, website_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $firstUser['id'],
                            $firstUser['name'],
                            'Fullstack Developer',
                            'Profil administrateur initial.',
                            $firstUser['email'],
                            'Africa/Porto-Novo',
                            '',
                            '',
                            '',
                        ]
                    );
                }
            }
            return;
        }

        $bootstrapAdmin = self::bootstrapAdmin();
        if ($bootstrapAdmin === null) {
            return;
        }

        Database::query('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)', [
            $bootstrapAdmin['name'],
            $bootstrapAdmin['email'],
            password_hash($bootstrapAdmin['password'], PASSWORD_DEFAULT),
            'super_admin',
        ]);

        $userId = (int) Database::lastInsertId();
        Database::query(
            'INSERT INTO profiles (user_id, full_name, title, bio, email, location, github_url, linkedin_url, website_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $bootstrapAdmin['name'], 'Fullstack Developer', 'Profil administrateur initial.', $bootstrapAdmin['email'], 'Africa/Porto-Novo', '', '', '']
        );
    }

    private static function storeSession(array $user): void
    {
        self::ensureSessionStarted();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_TTL;

        $model = new RememberToken();
        $model->purgeExpired();
        $model->deleteForUser($userId);
        $model->create([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        setcookie(self::REMEMBER_COOKIE, $selector . ':' . $token, [
            'expires' => $expires,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function forgetRememberedUser(?int $userId = null): void
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if ($cookie !== '' && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            (new RememberToken())->deleteBySelector($selector);
        }

        if ($userId !== null) {
            (new RememberToken())->deleteForUser($userId);
        }

        self::clearRememberCookie();
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    private static function destroySession(): void
    {
        self::ensureSessionStarted();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::ensureSessionStarted();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    private static function bootstrapAdmin(): ?array
    {
        $environment = strtolower(trim((string) env('APP_ENV', 'local')));
        $enabled = (bool) env('ADMIN_BOOTSTRAP_ENABLED', $environment !== 'production');
        if (!$enabled) {
            return null;
        }

        $name = trim((string) env('ADMIN_BOOTSTRAP_NAME', 'Admin'));
        $defaultEmail = $environment === 'production' ? '' : 'admin@portfolio.local';
        $defaultPassword = $environment === 'production' ? '' : 'Admin@2025!';
        $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', $defaultEmail));
        $password = trim((string) env('ADMIN_BOOTSTRAP_PASSWORD', $defaultPassword));

        if ($name === '' || !is_valid_email($email) || strlen($password) < 8) {
            return null;
        }

        if ($environment === 'production') {
            if (strlen($password) < 12 || self::usesPlaceholderBootstrapCredentials($email, $password)) {
                return null;
            }
        }

        return [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];
    }

    private static function usesPlaceholderBootstrapCredentials(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $password = strtolower(trim($password));

        if ($email === 'admin@example.com' || str_ends_with($email, '@example.com')) {
            return true;
        }

        foreach (['change_me', 'your_', 'example', 'admin@2025!', 'password'] as $needle) {
            if (str_contains($password, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        session_start();
    }
}
