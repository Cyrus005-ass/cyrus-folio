<?php

namespace App\Middleware;

class CsrfMiddleware
{
    public static function handle(): void
    {
        if (!csrf_validate($_POST['_csrf'] ?? '')) {
            flash('error', 'Jeton CSRF invalide.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
