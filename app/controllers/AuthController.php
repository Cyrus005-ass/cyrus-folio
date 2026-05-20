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

    public function loginForm(): void
    {
        if (auth_check()) {
            redirect('/admin');
        }
        $this->view('auth/login', [], 'auth');
    }

    public function login(): void
    {
        $data = is_api_request() ? $this->input() : $_POST;
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $remember = !empty($data['remember']);

        $retryAfter = $this->loginRetryAfter($email);
        if ($retryAfter > 0) {
            $message = 'Trop de tentatives de connexion. Reessaie dans ' . max(1, (int) ceil($retryAfter / 60)) . ' minute(s).';
            if (is_api_request()) {
                $this->json(['success' => false, 'message' => $message], 429);
            }

            flash('error', $message);
            redirect('/admin/login');
        }

        if (is_api_request()) {
            if (AuthService::login($email, $password, $remember)) {
                $this->clearLoginRateLimit($email);
                $this->json(['success' => true, 'user' => auth_user()]);
            }

            $this->hitLoginRateLimit($email);
            $this->json(['success' => false, 'message' => 'Identifiants invalides'], 422);
        }

        $this->validateCsrf();
        if (AuthService::login($email, $password, $remember)) {
            $this->clearLoginRateLimit($email);
            flash('success', 'Bienvenue dans ' . env('APP_NAME', 'Cyrus-y ASSOGBA') . '.');
            redirect('/admin');
        }

        $this->hitLoginRateLimit($email);
        flash('error', 'Email ou mot de passe invalide.');
        redirect('/admin/login');
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
}