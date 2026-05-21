<?php

namespace App\Services;

use App\Models\Theme;
use Throwable;

class ThemeService
{
    public static function activeTheme(): array
    {
        try {
            $theme = (new Theme())->active();
            if (is_array($theme) && $theme !== []) {
                return self::isLegacyDefaultTheme($theme) ? theme_defaults() : $theme;
            }

            return theme_defaults();
        } catch (Throwable) {
            return theme_defaults();
        }
    }

    public static function cssVariables(): string
    {
        $theme = self::activeTheme();
        $primary = (string) ($theme['primary_color'] ?? '#ff4d4f');
        $secondary = (string) ($theme['secondary_color'] ?? '#141414');
        $accent = (string) ($theme['accent_color'] ?? '#ff8f90');
        $background = (string) ($theme['background_color'] ?? '#0b0b0b');
        $text = (string) ($theme['text_color'] ?? '#f2ede8');
        $displayFont = (string) ($theme['display_font_family'] ?? $theme['font_family'] ?? 'Mulish, Segoe UI, sans-serif');
        $bodyFont = (string) ($theme['body_font_family'] ?? $theme['font_family'] ?? 'Roboto, Segoe UI, sans-serif');

        return ':root{' .
            '--primary:' . $primary . ';' .
            '--secondary:' . $secondary . ';' .
            '--accent:' . $accent . ';' .
            '--bg:' . $background . ';' .
            '--text:' . $text . ';' .
            '--font:' . $bodyFont . ';' .
            '--font-display:' . $displayFont . ';' .
            '--font-body:' . $bodyFont . ';' .
            '--font-ui:' . $displayFont . ';' .
            '--muted:color-mix(in srgb, ' . $text . ', transparent 34%);' .
            '--text-strong:color-mix(in srgb, ' . $text . ', #ffffff 10%);' .
            '--text-soft:color-mix(in srgb, ' . $text . ', transparent 16%);' .
            '--text-faint:color-mix(in srgb, ' . $text . ', transparent 50%);' .
            '--card:color-mix(in srgb, ' . $secondary . ', #ffffff 4%);' .
            '--surface:color-mix(in srgb, ' . $secondary . ', #ffffff 6%);' .
            '--surface-alt:color-mix(in srgb, ' . $secondary . ', #ffffff 10%);' .
            '--border:color-mix(in srgb, ' . $text . ', transparent 88%);' .
            '--border-strong:color-mix(in srgb, ' . $text . ', transparent 78%);' .
            '--primary-soft:color-mix(in srgb, ' . $primary . ', transparent 88%);' .
            '--primary-mid:color-mix(in srgb, ' . $primary . ', transparent 74%);' .
            '--primary-strong:color-mix(in srgb, ' . $primary . ', #ffffff 12%);' .
            '--shadow:0 28px 60px rgba(0, 0, 0, 0.32);' .
            '--shadow-soft:0 16px 36px rgba(0, 0, 0, 0.24);' .
            '--radius-xl:24px;' .
            '--radius-lg:18px;' .
            '--radius-md:16px;' .
            '--radius-sm:12px;' .
        '}';
    }

    public static function fontStylesheetUrl(?array $theme = null): ?string
    {
        if (save_data_enabled() || !(bool) env('APP_EXTERNAL_FONTS', true)) {
            return null;
        }

        $theme ??= self::activeTheme();
        $families = self::googleFontFamilies($theme);
        if ($families === []) {
            return null;
        }

        $parts = [];
        foreach ($families as $family => $weights) {
            $part = 'family=' . str_replace('%20', '+', rawurlencode($family));
            if ($weights !== []) {
                $part .= ':wght@' . implode(';', $weights);
            }
            $parts[] = $part;
        }

        $parts[] = 'display=swap';

        return 'https://fonts.googleapis.com/css2?' . implode('&', $parts);
    }

    public static function animationsEnabled(): bool
    {
        return !empty(self::activeTheme()['animations_enabled']);
    }

    private static function googleFontFamilies(array $theme): array
    {
        $catalog = self::googleFontCatalog();
        $families = [];

        $candidates = [
            self::normalizeFontFamilyName((string) ($theme['display_font_family'] ?? $theme['font_family'] ?? '')),
            self::normalizeFontFamilyName((string) ($theme['body_font_family'] ?? $theme['font_family'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            foreach ($catalog as $family => $weights) {
                if (strcasecmp($family, $candidate) === 0) {
                    $families[$family] = $weights;
                    break;
                }
            }
        }

        return $families;
    }

    private static function googleFontCatalog(): array
    {
        return [
            'Mulish' => ['400', '500', '600', '700', '800'],
            'Roboto' => ['400', '500', '700'],
            'Poppins' => ['400', '500', '600', '700', '800'],
            'Source Sans 3' => ['400', '600', '700'],
            'Raleway' => ['500', '600', '700', '800'],
        ];
    }

    private static function normalizeFontFamilyName(string $value): ?string
    {
        $first = trim((string) explode(',', $value, 2)[0], " \t\n\r\0\x0B'\"");
        if ($first === '') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $first) ?: $first;
    }

    private static function isLegacyDefaultTheme(array $theme): bool
    {
        $legacy = [
            'primary_color' => '#2563eb',
            'secondary_color' => '#111827',
            'accent_color' => '#f59e0b',
            'background_color' => '#f8fafc',
            'text_color' => '#111827',
            'display_font_family' => 'Poppins, Segoe UI, sans-serif',
            'body_font_family' => 'Source Sans 3, Segoe UI, sans-serif',
        ];

        foreach ($legacy as $key => $value) {
            if (trim((string) ($theme[$key] ?? '')) !== $value) {
                return false;
            }
        }

        return true;
    }
}
