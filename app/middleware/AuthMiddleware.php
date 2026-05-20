<?php

namespace App\Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!auth_check()) {
            redirect('/admin/login');
        }
    }
}
