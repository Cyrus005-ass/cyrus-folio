<?php

namespace App\Middleware;

use App\Services\AnalyticsService;

class AnalyticsMiddleware
{
    public static function handle(): void
    {
        AnalyticsService::track();
    }
}
