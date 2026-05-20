<?php

namespace App\Services;

use App\Models\Analytics;
use Throwable;

class AnalyticsService
{
    public static function track(): void
    {
        if (str_starts_with('/' . trim(current_uri(), '/'), '/admin') || str_starts_with('/' . trim(current_uri(), '/'), '/api')) {
            return;
        }

        try {
            $isNewSession = false;
            if (empty($_SESSION['visitor_session'])) {
                $_SESSION['visitor_session'] = bin2hex(random_bytes(16));
                $isNewSession = true;
            }

            [$countryCode, $country] = self::country();
            $model = new Analytics();
            $isNewCountry = $countryCode !== null && $model->count('country_code = ?', [$countryCode]) === 0;

            $model->create([
                'session_id' => $_SESSION['visitor_session'],
                'page' => current_uri(),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'device' => self::device($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'country' => $country,
                'country_code' => $countryCode,
            ]);

            if ($isNewSession) {
                ActivityService::log('visitor.new', 'Nouveau visiteur ' . ($country !== null ? 'depuis ' . $country : 'sur le portfolio'));
            }

            if ($isNewCountry && $country !== null) {
                NotificationService::push('analytics', 'Nouveau pays detecte', 'Premier visiteur depuis ' . $country . '.', '/admin/analytics', 'country-first:' . $countryCode);
            }
        } catch (Throwable) {
            // Ne jamais casser la navigation publique.
        }
    }

    private static function device(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        return 'desktop';
    }

    private static function country(): array
    {
        $rawCode = clean_nullable($_SERVER['GEOIP_COUNTRY_CODE'] ?? $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
        $rawCountry = clean_nullable($_SERVER['GEOIP_COUNTRY_NAME'] ?? '');

        $code = null;
        if ($rawCode !== null) {
            $candidate = strtoupper($rawCode);
            if (preg_match('/^[A-Z]{2}$/', $candidate) === 1) {
                $code = $candidate;
            }
        }

        $country = null;
        if ($rawCountry !== null) {
            $sanitized = preg_replace('/[^\p{L}\p{N}\s\-\'",\.\(\)]/u', '', $rawCountry) ?? $rawCountry;
            $sanitized = trim(preg_replace('/\s+/u', ' ', $sanitized) ?? $sanitized);
            if ($sanitized !== '') {
                $country = mb_substr($sanitized, 0, 100);
            }
        }

        if ($country === null && $code !== null) {
            $country = match ($code) {
                'BJ' => 'Benin',
                'FR' => 'France',
                'US' => 'Etats-Unis',
                'CA' => 'Canada',
                'GB' => 'Royaume-Uni',
                'NG' => 'Nigeria',
                'DE' => 'Allemagne',
                default => $code,
            };
        }

        return [$code, $country];
    }
}