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
    private const TWO_FACTOR_PENDING_KEY = 'auth.two_factor_pending';
    private const TWO_FACTOR_PENDING_TTL = 600;

    public static function login(string $email, string $password, bool $remember = false): bool
    {
        $result = self::attemptLogin($email, $password, $remember);
        if (!empty($result['requires_two_factor'])) {
            self::clearTwoFactorChallenge();
            return false;
        }

        return !empty($result['success']);
    }

    public static function attemptLogin(string $email, string $password, bool $remember = false): array
    {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !(int) ($user['is_active'] ?? 0) || !password_verify($password, (string) ($user['password'] ?? ''))) {
            return [
                'success' => false,
                'requires_two_factor' => false,
                'message' => 'Identifiants invalides',
            ];
        }

        return self::authorizeUser($user, $remember, 'local');
    }

    public static function logout(): void
    {
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        ActivityService::log('logout', 'Deconnexion administrateur');
        self::clearTwoFactorChallenge();
        self::forgetRememberedUser($userId > 0 ? $userId : null);
        self::destroySession();
    }

    public static function loginWithFirebaseIdToken(string $idToken, bool $remember = false): bool
    {
        $result = self::attemptFirebaseLogin($idToken, $remember);
        if (!empty($result['requires_two_factor'])) {
            self::clearTwoFactorChallenge();
            return false;
        }

        return !empty($result['success']);
    }

    public static function attemptFirebaseLogin(string $idToken, bool $remember = false): array
    {
        try {
            $claims = FirebaseService::verifyIdToken($idToken);
            $user = self::resolveFirebaseUser($claims);
        } catch (Throwable) {
            return [
                'success' => false,
                'requires_two_factor' => false,
                'message' => 'Jeton Firebase invalide ou utilisateur local non autorise',
            ];
        }

        if (!$user || !(int) ($user['is_active'] ?? 0)) {
            return [
                'success' => false,
                'requires_two_factor' => false,
                'message' => 'Jeton Firebase invalide ou utilisateur local non autorise',
            ];
        }

        return self::authorizeUser($user, $remember, 'firebase');
    }

    public static function pendingTwoFactorUser(): ?array
    {
        $challenge = self::pendingTwoFactorChallenge();
        if ($challenge === null) {
            return null;
        }

        $user = (new User())->find((int) ($challenge['user_id'] ?? 0));
        if (!$user || !(int) ($user['is_active'] ?? 0) || !TwoFactorService::enabledForUser($user)) {
            self::clearTwoFactorChallenge();
            return null;
        }

        return self::sanitizeSessionUser($user);
    }

    public static function completeTwoFactorLogin(string $code): array
    {
        $challenge = self::pendingTwoFactorChallenge();
        if ($challenge === null) {
            return [
                'success' => false,
                'expired' => true,
                'message' => 'La verification 2FA a expire. Connecte-toi a nouveau.',
            ];
        }

        $user = (new User())->find((int) ($challenge['user_id'] ?? 0));
        if (!$user || !(int) ($user['is_active'] ?? 0) || !TwoFactorService::enabledForUser($user)) {
            self::clearTwoFactorChallenge();
            return [
                'success' => false,
                'expired' => true,
                'message' => 'Le compte 2FA n est plus disponible. Reconnecte-toi.',
            ];
        }

        if (!TwoFactorService::verifyCode((string) ($user['two_factor_secret'] ?? ''), $code)) {
            ActivityService::log('two_factor_failed', 'Code 2FA invalide pendant la connexion.', (int) $user['id']);
            return [
                'success' => false,
                'expired' => false,
                'message' => 'Code de verification invalide.',
            ];
        }

        self::finalizeLogin($user, (bool) ($challenge['remember'] ?? false), (string) ($challenge['driver'] ?? 'local'), true);

        return [
            'success' => true,
            'expired' => false,
            'auth_driver' => (string) ($challenge['driver'] ?? 'local'),
            'user' => auth_user(),
        ];
    }

    public static function revokeRememberTokens(int $userId): void
    {
        self::forgetRememberedUser($userId);
    }

    public static function restoreRememberedUser(): void
    {
        if (auth_check() || self::hasPendingTwoFactor()) {
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
        if (!$user || !(int) ($user['is_active'] ?? 0) || TwoFactorService::enabledForUser($user)) {
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
                self::ensureProfileExists((int) $firstUser['id'], (string) $firstUser['name'], (string) $firstUser['email']);
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
        self::ensureProfileExists($userId, $bootstrapAdmin['name'], $bootstrapAdmin['email']);
    }

    private static function authorizeUser(array $user, bool $remember, string $driver): array
    {
        if (TwoFactorService::enabledForUser($user)) {
            self::beginTwoFactorChallenge($user, $remember, $driver);

            return [
                'success' => false,
                'requires_two_factor' => true,
                'message' => 'V?rification 2FA requise.',
            ];
        }

        self::finalizeLogin($user, $remember, $driver, false);

        return [
            'success' => true,
            'requires_two_factor' => false,
            'auth_driver' => $driver,
            'user' => auth_user(),
        ];
    }

    private static function beginTwoFactorChallenge(array $user, bool $remember, string $driver): void
    {
        self::ensureSessionStarted();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION['user']);
        $_SESSION[self::TWO_FACTOR_PENDING_KEY] = [
            'user_id' => (int) ($user['id'] ?? 0),
            'remember' => $remember,
            'driver' => $driver,
            'issued_at' => time(),
        ];
        session_regenerate_id(true);

        ActivityService::log('two_factor_challenge', 'V?rification 2FA requise avant la connexion administrateur.', (int) ($user['id'] ?? 0));
    }

    private static function finalizeLogin(array $user, bool $remember, string $driver, bool $twoFactorVerified): void
    {
        self::clearTwoFactorChallenge();
        self::storeSession($user);
        (new User())->updateLastLogin((int) $user['id']);

        $remember = $remember && !TwoFactorService::enabledForUser($user);
        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        } else {
            self::forgetRememberedUser((int) $user['id']);
        }

        $action = $driver === 'firebase' ? 'login_firebase' : 'login';
        $description = $driver === 'firebase'
            ? 'Connexion administrateur via Firebase'
            : 'Connexion administrateur';

        if ($twoFactorVerified) {
            $action .= '_2fa';
            $description .= ' avec verification 2FA';
        }

        ActivityService::log($action, $description, (int) $user['id']);
    }

    private static function hasPendingTwoFactor(): bool
    {
        return self::pendingTwoFactorChallenge() !== null;
    }

    private static function pendingTwoFactorChallenge(): ?array
    {
        self::ensureSessionStarted();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $challenge = $_SESSION[self::TWO_FACTOR_PENDING_KEY] ?? null;
        if (!is_array($challenge)) {
            return null;
        }

        $userId = (int) ($challenge['user_id'] ?? 0);
        $issuedAt = (int) ($challenge['issued_at'] ?? 0);
        if ($userId <= 0 || $issuedAt <= 0 || ($issuedAt + self::TWO_FACTOR_PENDING_TTL) < time()) {
            self::clearTwoFactorChallenge();
            return null;
        }

        return $challenge;
    }

    private static function clearTwoFactorChallenge(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || self::ensureSessionStarted()) {
            unset($_SESSION[self::TWO_FACTOR_PENDING_KEY]);
        }
    }

    private static function resolveFirebaseUser(array $claims): ?array
    {
        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if (!is_valid_email($email)) {
            return null;
        }

        $requiresVerifiedEmail = (bool) env('FIREBASE_REQUIRE_VERIFIED_EMAIL', true);
        if ($requiresVerifiedEmail && !self::firebaseEmailVerified($claims['email_verified'] ?? false)) {
            return null;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if ($user) {
            self::ensureProfileExists((int) $user['id'], (string) ($user['name'] ?? 'Admin'), $email);
            return $user;
        }

        if (!(bool) env('FIREBASE_AUTO_PROVISION', false)) {
            return null;
        }

        $name = trim((string) ($claims['name'] ?? ''));
        if ($name === '') {
            $name = ucfirst((string) strtok($email, '@'));
        }

        $role = trim((string) env('FIREBASE_AUTO_PROVISION_ROLE', 'super_admin'));
        if ($role === '') {
            $role = 'super_admin';
        }

        Database::query('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)', [
            $name,
            $email,
            password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
            $role,
        ]);

        $userId = (int) Database::lastInsertId();
        self::ensureProfileExists($userId, $name, $email);

        return $userModel->find($userId);
    }

    private static function firebaseEmailVerified(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    private static function ensureProfileExists(int $userId, string $name, string $email): void
    {
        $profileCount = (int) Database::query('SELECT COUNT(*) total FROM profiles WHERE user_id = ?', [$userId])->fetch()['total'];
        if ($profileCount > 0) {
            return;
        }

        Database::query(
            'INSERT INTO profiles (user_id, full_name, title, bio, email, location, github_url, linkedin_url, website_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $name, 'Fullstack Developer', 'Profil administrateur initial.', $email, 'Africa/Porto-Novo', '', '', '']
        );
    }

    private static function storeSession(array $user): void
    {
        self::ensureSessionStarted();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = self::sanitizeSessionUser($user);
    }

    private static function sanitizeSessionUser(array $user): array
    {
        unset($user['password'], $user['two_factor_secret']);
        return $user;
    }

    private static function isSecureRequest(): bool
    {
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || $forwardedProto === 'https';
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
            'secure' => self::isSecureRequest(),
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
            'secure' => self::isSecureRequest(),
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
                'secure' => self::isSecureRequest(),
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

    private static function ensureSessionStarted(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        session_start();
        return session_status() === PHP_SESSION_ACTIVE;
    }
}