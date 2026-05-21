<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\RateLimiter;

class AuthController extends Controller
{
    private const LOGIN_WINDOW_SECONDS = 900;
    private const LOGIN_MAX_ATTEMPTS_PER_IP = 15;
    private const LOGIN_MAX_ATTEMPTS_PER_IDENTITY = 5;
    private const TWO_FACTOR_WINDOW_SECONDS = 600;
    private const TWO_FACTOR_MAX_ATTEMPTS = 8;

    public function loginForm(): void
    {
        if (auth_check()) {
            redirect('/admin');
        }
        $this->view('auth/login', [], 'auth');
    }

    public function twoFactorForm(): void
    {
        if (auth_check()) {
            redirect('/admin');
        }

        $pendingUser = AuthService::pendingTwoFactorUser();
        if ($pendingUser === null) {
            flash('warning', 'La verification 2FA a expire. Connecte-toi a nouveau.');
            redirect('/admin/login');
        }

        $this->view('auth/two-factor', ['pendingUser' => $pendingUser], 'auth');
    }

    public function login(): void
    {
        $data = is_api_request() ? $this->input() : $_POST;
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $remember = !empty($data['remember']);
        $firebaseIdToken = is_api_request() ? $this->firebaseIdToken($data) : '';
        $rateLimitIdentity = $email !== '' ? $email : ($firebaseIdToken !== '' ? 'firebase-token' : '');

        $retryAfter = $this->loginRetryAfter($rateLimitIdentity);
        if ($retryAfter > 0) {
            $message = 'Trop de tentatives de connexion. Reessaie dans ' . max(1, (int) ceil($retryAfter / 60)) . ' minute(s).';
            if (is_api_request()) {
                $this->json(['success' => false, 'message' => $message], 429);
            }

            flash('error', $message);
            redirect('/admin/login');
        }

        if (!is_api_request()) {
            $this->validateCsrf();
        }

        $result = ($firebaseIdToken !== '' && is_api_request())
            ? AuthService::attemptFirebaseLogin($firebaseIdToken, $remember)
            : AuthService::attemptLogin($email, $password, $remember);

        if (!empty($result['success'])) {
            $this->clearLoginRateLimit($rateLimitIdentity);

            if (is_api_request()) {
                $this->json([
                    'success' => true,
                    'user' => auth_user(),
                    'auth_driver' => (string) ($result['auth_driver'] ?? 'local'),
                ]);
            }

            flash('success', 'Bienvenue dans ' . env('APP_NAME', 'Cyrus-y ASSOGBA') . '.');
            redirect('/admin');
        }

        if (!empty($result['requires_two_factor'])) {
            $this->clearLoginRateLimit($rateLimitIdentity);

            if (is_api_request()) {
                $this->json([
                    'success' => false,
                    'requires_two_factor' => true,
                    'message' => (string) ($result['message'] ?? 'V?rification 2FA requise.'),
                    'next_step' => url('/admin/2fa/verify'),
                ], 202);
            }

            flash('warning', 'V?rification 2FA requise. Entre le code de ton application d\'authentification.');
            redirect('/admin/2fa/verify');
        }

        $this->hitLoginRateLimit($rateLimitIdentity);
        $message = (string) ($result['message'] ?? 'Email ou mot de passe invalide.');

        if (is_api_request()) {
            $this->json(['success' => false, 'message' => $message], 422);
        }

        flash('error', $message);
        redirect('/admin/login');
    }

    public function twoFactorVerify(): void
    {
        if (auth_check()) {
            if (is_api_request()) {
                $this->json(['success' => true, 'user' => auth_user()]);
            }
            redirect('/admin');
        }

        $pendingUser = AuthService::pendingTwoFactorUser();
        if ($pendingUser === null) {
            $message = 'La verification 2FA a expire. Connecte-toi a nouveau.';
            if (is_api_request()) {
                $this->json(['success' => false, 'message' => $message], 401);
            }

            flash('warning', $message);
            redirect('/admin/login');
        }

        $retryAfter = $this->twoFactorRetryAfter($pendingUser);
        if ($retryAfter > 0) {
            $message = 'Trop de tentatives 2FA. Reessaie dans ' . max(1, (int) ceil($retryAfter / 60)) . ' minute(s).';
            if (is_api_request()) {
                $this->json(['success' => false, 'message' => $message], 429);
            }

            flash('error', $message);
            redirect('/admin/2fa/verify');
        }

        $data = is_api_request() ? $this->input() : $_POST;
        if (!is_api_request()) {
            $this->validateCsrf();
        }

        $result = AuthService::completeTwoFactorLogin((string) ($data['code'] ?? $data['two_factor_code'] ?? ''));
        if (!empty($result['success'])) {
            $this->clearTwoFactorRateLimit($pendingUser);

            if (is_api_request()) {
                $this->json([
                    'success' => true,
                    'user' => auth_user(),
                    'auth_driver' => (string) ($result['auth_driver'] ?? 'local'),
                ]);
            }

            flash('success', 'V?rification 2FA r?ussie.');
            redirect('/admin');
        }

        if (!empty($result['expired'])) {
            $message = (string) ($result['message'] ?? 'La verification 2FA a expire.');
            if (is_api_request()) {
                $this->json(['success' => false, 'message' => $message], 401);
            }

            flash('warning', $message);
            redirect('/admin/login');
        }

        $this->hitTwoFactorRateLimit($pendingUser);
        $message = (string) ($result['message'] ?? 'Code de verification invalide.');

        if (is_api_request()) {
            $this->json(['success' => false, 'message' => $message], 422);
        }

        flash('error', $message);
        redirect('/admin/2fa/verify');
    }

    public function logout(): void
    {
        AuthService::logout();
        if (is_api_request()) {
            $this->json(['success' => true]);
        }
        redirect('/admin/login');
    }

    private function hitLoginRateLimit(string $email): void
    {
        [$ipKey, $identityKey] = $this->loginRateLimitKeys($email);
        RateLimiter::hit($ipKey, self::LOGIN_WINDOW_SECONDS);
        RateLimiter::hit($identityKey, self::LOGIN_WINDOW_SECONDS);
    }

    private function clearLoginRateLimit(string $email): void
    {
        [$ipKey, $identityKey] = $this->loginRateLimitKeys($email);
        RateLimiter::clear($ipKey);
        RateLimiter::clear($identityKey);
    }

    private function loginRetryAfter(string $email): int
    {
        [$ipKey, $identityKey] = $this->loginRateLimitKeys($email);
        $retryAfter = 0;

        if (RateLimiter::tooManyAttempts($ipKey, self::LOGIN_MAX_ATTEMPTS_PER_IP, self::LOGIN_WINDOW_SECONDS)) {
            $retryAfter = max($retryAfter, RateLimiter::retryAfter($ipKey, self::LOGIN_MAX_ATTEMPTS_PER_IP, self::LOGIN_WINDOW_SECONDS));
        }

        if (RateLimiter::tooManyAttempts($identityKey, self::LOGIN_MAX_ATTEMPTS_PER_IDENTITY, self::LOGIN_WINDOW_SECONDS)) {
            $retryAfter = max($retryAfter, RateLimiter::retryAfter($identityKey, self::LOGIN_MAX_ATTEMPTS_PER_IDENTITY, self::LOGIN_WINDOW_SECONDS));
        }

        return $retryAfter;
    }

    private function loginRateLimitKeys(string $email): array
    {
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $identity = strtolower(trim($email));
        if ($identity === '') {
            $identity = 'anonymous';
        }

        return [
            'auth:login:ip:' . $ip,
            'auth:login:identity:' . sha1($ip . '|' . $identity),
        ];
    }

    private function hitTwoFactorRateLimit(array $pendingUser): void
    {
        RateLimiter::hit($this->twoFactorRateLimitKey($pendingUser), self::TWO_FACTOR_WINDOW_SECONDS);
    }

    private function clearTwoFactorRateLimit(array $pendingUser): void
    {
        RateLimiter::clear($this->twoFactorRateLimitKey($pendingUser));
    }

    private function twoFactorRetryAfter(array $pendingUser): int
    {
        $key = $this->twoFactorRateLimitKey($pendingUser);
        if (!RateLimiter::tooManyAttempts($key, self::TWO_FACTOR_MAX_ATTEMPTS, self::TWO_FACTOR_WINDOW_SECONDS)) {
            return 0;
        }

        return RateLimiter::retryAfter($key, self::TWO_FACTOR_MAX_ATTEMPTS, self::TWO_FACTOR_WINDOW_SECONDS);
    }

    private function twoFactorRateLimitKey(array $pendingUser): string
    {
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $userId = (int) ($pendingUser['id'] ?? 0);

        return 'auth:2fa:' . $ip . ':' . $userId;
    }

    private function firebaseIdToken(array $data): string
    {
        foreach (['firebase_id_token', 'firebaseIdToken', 'idToken'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return trim((string) (request_bearer_token() ?? ''));
    }
}