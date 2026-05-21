<?php

namespace App\Services;

use App\Core\Database;
use Throwable;

class SystemHealthService
{
    public static function dashboardReport(): array
    {
        $checks = [
            self::databaseCheck(),
            self::rememberTokensCheck(),
            self::uploadsCheck(),
            self::firebaseCheck(),
            self::groqCheck(),
            self::runtimeCheck(),
        ];

        $summary = [
            'healthy' => 0,
            'warning' => 0,
            'danger' => 0,
            'info' => 0,
        ];

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'info');
            if (!array_key_exists($status, $summary)) {
                $status = 'info';
            }
            $summary[$status]++;
        }

        return [
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    private static function databaseCheck(): array
    {
        $meta = trim((string) env('DB_HOST', '127.0.0.1')) . ':' . trim((string) env('DB_PORT', '3306')) . ' / ' . trim((string) env('DB_NAME', 'portfolio_os'));

        try {
            Database::query('SELECT 1')->fetch();

            return [
                'title' => 'Base de donn?es',
                'status' => 'healthy',
                'detail' => 'Connexion PDO active et requ?tes SQL disponibles.',
                'meta' => $meta,
            ];
        } catch (Throwable $e) {
            return [
                'title' => 'Base de donn?es',
                'status' => 'danger',
                'detail' => 'Connexion impossible ou requ?te de test en ?chec.',
                'meta' => $meta . ' | ' . $e->getMessage(),
            ];
        }
    }

    private static function rememberTokensCheck(): array
    {
        try {
            Database::query('SELECT 1 FROM remember_tokens LIMIT 1')->fetch();

            return [
                'title' => 'Sessions persistantes',
                'status' => 'healthy',
                'detail' => 'La table remember_tokens est disponible pour le remember me.',
                'meta' => 'remember_tokens OK',
            ];
        } catch (Throwable $e) {
            return [
                'title' => 'Sessions persistantes',
                'status' => 'warning',
                'detail' => 'Le remember me ne pourra pas fonctionner tant que la table n\'est pas exploitable.',
                'meta' => $e->getMessage(),
            ];
        }
    }

    private static function uploadsCheck(): array
    {
        $uploadsDir = public_path('assets/uploads');
        if (is_dir($uploadsDir)) {
            return [
                'title' => 'Uploads',
                'status' => is_writable($uploadsDir) ? 'healthy' : 'danger',
                'detail' => is_writable($uploadsDir)
                    ? 'Le dossier des m?dias existe et accepte les uploads.'
                    : 'Le dossier des m?dias existe mais n\'est pas inscriptible.',
                'meta' => $uploadsDir,
            ];
        }

        $parentDir = dirname($uploadsDir);
        return [
            'title' => 'Uploads',
            'status' => (is_dir($parentDir) && is_writable($parentDir)) ? 'warning' : 'danger',
            'detail' => (is_dir($parentDir) && is_writable($parentDir))
                ? 'Le dossier sera cr?? au premier upload.'
                : 'Le dossier ne peut pas ?tre cr?? avec les droits actuels.',
            'meta' => $uploadsDir,
        ];
    }

    private static function firebaseCheck(): array
    {
        if (!(bool) env('FIREBASE_ENABLED', false)) {
            return [
                'title' => 'Firebase',
                'status' => 'info',
                'detail' => 'Int?gration Firebase d?sactiv?e dans cet environnement.',
                'meta' => 'FIREBASE_ENABLED=false',
            ];
        }

        $projectId = FirebaseService::projectId();
        $credentialsPath = FirebaseService::credentialsPath();
        $syncEnabled = (bool) env('FIREBASE_MESSAGES_SYNC', false);
        $meta = trim($projectId) !== '' ? $projectId : 'Projet non configur?';
        if (is_string($credentialsPath) && $credentialsPath !== '') {
            $meta .= ' | ' . basename($credentialsPath);
        }

        if (FirebaseService::adminApiEnabled()) {
            return [
                'title' => 'Firebase',
                'status' => 'healthy',
                'detail' => $syncEnabled
                    ? 'Admin API pr?te et synchro messages live activ?e.'
                    : 'Admin API pr?te, synchro messages d?sactiv?e.',
                'meta' => $meta,
            ];
        }

        return [
            'title' => 'Firebase',
            'status' => 'danger',
            'detail' => $syncEnabled
                ? 'Firebase est active mais les credentials admin sont invalides ou manquants, la synchro live est indisponible.'
                : 'Firebase est active mais les credentials admin sont invalides ou manquants.',
            'meta' => $meta,
        ];
    }

    private static function groqCheck(): array
    {
        $apiKey = trim((string) env('GROQ_API_KEY', env('XAI_API_KEY', '')));
        $model = trim((string) env('GROQ_MODEL', env('XAI_MODEL', 'llama-3.3-70b-versatile')));

        if ($apiKey !== '') {
            return [
                'title' => 'Chatbot distant',
                'status' => 'healthy',
                'detail' => 'Le chatbot peut interroger Groq en plus du fallback local.',
                'meta' => $model,
            ];
        }

        return [
            'title' => 'Chatbot distant',
            'status' => 'warning',
            'detail' => 'Aucune cl? distante configur?e, le chatbot r?pond uniquement via sa base locale.',
            'meta' => $model,
        ];
    }

    private static function runtimeCheck(): array
    {
        $appEnv = strtolower(trim((string) env('APP_ENV', 'production')));
        $debug = (bool) env('APP_DEBUG', false);
        $meta = 'APP_ENV=' . $appEnv . ' | APP_DEBUG=' . ($debug ? 'true' : 'false');

        if ($appEnv === 'local') {
            return [
                'title' => 'Runtime',
                'status' => 'info',
                'detail' => 'Mode local actif, debug acceptable pour le d?veloppement.',
                'meta' => $meta,
            ];
        }

        if ($debug) {
            return [
                'title' => 'Runtime',
                'status' => 'danger',
                'detail' => 'APP_DEBUG est actif hors environnement local.',
                'meta' => $meta,
            ];
        }

        return [
            'title' => 'Runtime',
            'status' => 'healthy',
            'detail' => 'Configuration runtime stable pour un usage public.',
            'meta' => $meta,
        ];
    }
}
