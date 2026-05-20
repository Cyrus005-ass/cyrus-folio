<?php

namespace App\Services;

use App\Models\Activity;
use Throwable;

class ActivityService
{
    public static function log(string $action, string $description, ?int $userId = null): void
    {
        try {
            (new Activity())->create([
                'user_id' => $userId ?? ($_SESSION['user']['id'] ?? null),
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            // Ne bloque jamais l'application pour un log.
        }
    }
}
