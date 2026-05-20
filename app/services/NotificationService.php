<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\Notification;
use Throwable;

class NotificationService
{
    public static function push(string $type, string $titre, string $message, ?string $lien = null, ?string $uniqueKey = null): void
    {
        try {
            $model = new Notification();
            if ($uniqueKey !== null && $model->findByUniqueKey($uniqueKey)) {
                return;
            }

            $model->create([
                'type' => $type,
                'unique_key' => $uniqueKey,
                'titre' => $titre,
                'message' => $message,
                'lien' => $lien,
            ]);
        } catch (Throwable) {
            // Silencieux.
        }
    }

    public static function syncCertificationAlerts(int $days = 30): void
    {
        try {
            foreach ((new Certification())->expiringSoon($days) as $certification) {
                $date = (string) ($certification['date_expiration'] ?? '');
                self::push(
                    'certification',
                    'Certification a surveiller',
                    (string) ($certification['titre'] ?? 'Certification') . ' expire le ' . $date . '.',
                    '/admin/certifications',
                    'certification-expiry:' . (int) ($certification['id'] ?? 0) . ':' . $date
                );
            }
        } catch (Throwable) {
            // Silencieux.
        }
    }
}
