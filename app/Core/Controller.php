<?php

namespace App\Core;

class Controller
{
    protected function view(string $path, array $data = [], string $layout = 'public'): void
    {
        view($path, $data, $layout);
    }

    protected function json(array $payload, int $status = 200): void
    {
        json_response($payload, $status);
    }

    protected function requireAdmin(): void
    {
        if (!auth_check()) {
            if (is_api_request()) {
                json_response(['success' => false, 'message' => 'Authentification requise'], 401);
            }
            redirect('/admin/login');
        }
    }

    protected function validateCsrf(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? '')) {
            flash('error', 'Jeton de sécurité invalide. Veuillez réessayer.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/admin');
        }
    }

    protected function fail(string $message, string $redirectPath, int $status = 422, array $errors = []): never
    {
        if (is_api_request()) {
            $payload = ['success' => false, 'message' => $message];
            if ($errors !== []) {
                $payload['errors'] = $errors;
            }
            json_response($payload, $status);
        }

        flash('error', $message);
        redirect($redirectPath);
    }

    protected function input(): array
    {
        return request_data();
    }
}
