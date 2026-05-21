<?php

use App\Core\Env;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_config')) {
    function app_config(string $key = '', mixed $default = null): mixed
    {
        $config = defined('APP_CONFIG') && is_array(APP_CONFIG) ? APP_CONFIG : [];
        if ($key === '') {
            return $config;
        }

        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return PUBLIC_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('save_data_enabled')) {
    function save_data_enabled(): bool
    {
        return strtolower(trim((string) ($_SERVER['HTTP_SAVE_DATA'] ?? ''))) === 'on';
    }
}

if (!function_exists('normalize_host_name')) {
    function normalize_host_name(?string $host): string
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return '';
        }

        if (str_starts_with($host, '[') && str_contains($host, ']')) {
            $host = substr($host, 1, max(0, strpos($host, ']') - 1));
        } else {
            $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        }

        return preg_replace('/^www\./', '', $host) ?? $host;
    }
}

if (!function_exists('is_local_host_name')) {
    function is_local_host_name(?string $host): bool
    {
        $host = normalize_host_name($host);
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1', 'host.docker.internal'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host) === 1;
        }

        return str_ends_with($host, '.local')
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.internal');
    }
}

if (!function_exists('request_base_url')) {
    function request_base_url(): ?string
    {
        $hostHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($hostHeader === '') {
            return null;
        }

        $hostParts = array_values(array_filter(array_map('trim', explode(',', $hostHeader))));
        $host = $hostParts[0] ?? '';
        if ($host === '') {
            return null;
        }

        $forwardedProto = trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '') {
            $protoParts = array_values(array_filter(array_map('trim', explode(',', strtolower($forwardedProto)))));
            $scheme = in_array(($protoParts[0] ?? ''), ['http', 'https'], true) ? $protoParts[0] : 'https';
        } else {
            $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
            $scheme = (($https !== '' && $https !== 'off') || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443') ? 'https' : 'http';
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim($scriptDir, '/');
        if ($scriptDir === '/public') {
            $scriptDir = '';
        } elseif (str_ends_with($scriptDir, '/public')) {
            $scriptDir = substr($scriptDir, 0, -strlen('/public')) ?: '';
        }
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }

        return rtrim($scheme . '://' . $host . $scriptDir, '/');
    }
}

if (!function_exists('public_app_url')) {
    function public_app_url(): string
    {
        return app_url();
    }
}

if (!function_exists('normalize_app_path')) {
    function normalize_app_path(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || $path === '/') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');
        $projectDirectory = trim(str_replace('\\', '/', basename(BASE_PATH)), '/');
        $prefixes = [];

        $runtimeBasePath = parse_url((string) request_base_url(), PHP_URL_PATH);
        if (is_string($runtimeBasePath)) {
            $runtimeBasePath = rtrim($runtimeBasePath, '/');
            if ($runtimeBasePath !== '' && $runtimeBasePath !== '/') {
                $prefixes[] = $runtimeBasePath;
            }
        }

        if ($projectDirectory !== '') {
            $prefixes[] = '/' . $projectDirectory;
        }

        $prefixes[] = '/public';

        foreach (array_values(array_unique($prefixes)) as $prefix) {
            if ($prefix === '' || $prefix === '/') {
                continue;
            }

            if ($path === $prefix) {
                $path = '/';
                continue;
            }

            if (str_starts_with($path, $prefix . '/')) {
                $path = substr($path, strlen($prefix)) ?: '/';
            }
        }

        return $path === '' ? '/' : '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_url')) {
    function app_url(): string
    {
        $configured = rtrim((string) env('APP_URL', ''), '/');
        $runtime = request_base_url();
        if ($runtime === null || $runtime === '') {
            return $configured;
        }

        if ($configured === '') {
            return $runtime;
        }

        $configuredHost = normalize_host_name((string) parse_url($configured, PHP_URL_HOST));
        $runtimeHost = normalize_host_name((string) parse_url($runtime, PHP_URL_HOST));
        if ($runtimeHost !== '' && ($runtimeHost === $configuredHost || is_local_host_name($runtimeHost))) {
            return $runtime;
        }

        return $configured;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = app_url();
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('should_use_minified_assets')) {
    function should_use_minified_assets(): bool
    {
        $explicit = env('ASSET_MINIFY', null);
        if ($explicit !== null) {
            return (bool) $explicit;
        }

        return strtolower((string) env('APP_ENV', 'local')) === 'production' && !((bool) env('APP_DEBUG', false));
    }
}

if (!function_exists('minified_asset_path')) {
    function minified_asset_path(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_starts_with($path, 'vendor/') || preg_match('/\.min\.(?:js|css)$/i', $path) === 1) {
            return $path;
        }

        if (!should_use_minified_assets()) {
            return $path;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['js', 'css'], true)) {
            return $path;
        }

        $dirname = (string) pathinfo($path, PATHINFO_DIRNAME);
        $filename = (string) pathinfo($path, PATHINFO_FILENAME);
        $minified = ($dirname !== '' && $dirname !== '.' ? $dirname . '/' : '') . $filename . '.min.' . $extension;

        return is_file(public_path('assets/' . $minified)) ? $minified : $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . minified_asset_path($path));
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            $parts = parse_url($path);
            if ($parts === false) {
                return $path;
            }

            $host = normalize_host_name((string) ($parts['host'] ?? ''));
            if (!is_local_host_name($host)) {
                return $path;
            }

            $base = public_app_url();
            if ($base === '') {
                return $path;
            }

            $resolvedPath = normalize_app_path((string) ($parts['path'] ?? '/'));
            $resolved = rtrim($base, '/') . ($resolvedPath === '/' ? '' : $resolvedPath);
            if (!empty($parts['query'])) {
                $resolved .= '?' . $parts['query'];
            }
            if (!empty($parts['fragment'])) {
                $resolved .= '#' . $parts['fragment'];
            }

            return $resolved;
        }

        return url(normalize_app_path($path));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
        exit;
    }
}

if (!function_exists('view')) {
    function view(string $path, array $data = [], string $layout = 'public'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = RESOURCE_PATH . '/views/' . $path . '.php';
        if (!is_file($viewFile)) {
            $viewFile = RESOURCE_PATH . '/views/errors/404.php';
        }
        if (!is_file($viewFile)) {
            http_response_code(404);
            echo '<main style=padding:40px;font-family:sans-serif>Vue introuvable : ' . e($path) . '</main>';
            return;
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = RESOURCE_PATH . '/layouts/' . $layout . '.php';
        if (is_file($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $length = 140): string
    {
        $text = trim(strip_tags((string) $text));
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, max(1, $length - 3)) . '...';
    }
}
if (!function_exists('active_class')) {
    function active_class(string $path): string
    {
        $current = '/' . trim(current_uri(), '/');
        $path = '/' . trim($path, '/');
        return $current === $path || ($path !== '/' && str_starts_with($current, $path)) ? 'active' : '';
    }
}

if (!function_exists('decode_json_array')) {
    function decode_json_array(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded)
            ? array_values(array_filter($decoded, fn ($item) => is_string($item) && trim($item) !== ''))
            : [];
    }
}

if (!function_exists('encode_json_array')) {
    function encode_json_array(array $items): ?string
    {
        $items = array_values(array_filter(array_map(fn ($item) => trim((string) $item), $items), fn ($item) => $item !== ''));
        if ($items === []) {
            return null;
        }

        $json = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : null;
    }
}

if (!function_exists('skill_level_options')) {
    function skill_level_options(): array
    {
        return ['Notions', 'Intermediaire', 'Avance', 'Expert'];
    }
}

if (!function_exists('skill_category_options')) {
    function skill_category_options(): array
    {
        return ['Langages', 'Frameworks', 'Securite', 'Outils', 'Autre'];
    }
}

if (!function_exists('availability_options')) {
    function availability_options(): array
    {
        return ['disponible', 'non_disponible', 'en_mission'];
    }
}

if (!function_exists('skill_level_percent')) {
    function skill_level_percent(mixed $value): int
    {
        return match ((string) $value) {
            'Notions' => 25,
            'Intermediaire' => 55,
            'Avance' => 80,
            'Expert' => 100,
            default => is_numeric($value) ? max(0, min(100, (int) $value)) : 0,
        };
    }
}

if (!function_exists('theme_defaults')) {
    function theme_defaults(): array
    {
        return [
            'nom' => 'Craftivo Dark',
            'primary_color' => '#ff4d4f',
            'secondary_color' => '#141414',
            'accent_color' => '#ff8f90',
            'background_color' => '#0b0b0b',
            'text_color' => '#f2ede8',
            'display_font_family' => 'Mulish, Segoe UI, sans-serif',
            'body_font_family' => 'Roboto, Segoe UI, sans-serif',
            'font_family' => 'Roboto, Segoe UI, sans-serif',
            'animations_enabled' => 1,
            'is_active' => 1,
        ];
    }
}

if (!function_exists('format_seconds_short')) {
    function format_seconds_short(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 min';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        if ($minutes <= 0) {
            return $remaining . ' sec';
        }

        return $minutes . ' min' . ($remaining > 0 ? ' ' . $remaining . ' sec' : '');
    }
}

if (!function_exists('parse_named_links')) {
    function parse_named_links(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $links = [];
        foreach (preg_split('/\r\n|\r|\n/', $value) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $resolvedUrl = absolute_url($url);
            if ($resolvedUrl === null || filter_var($resolvedUrl, FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $links[] = [
                'label' => $label !== '' ? $label : (parse_url($resolvedUrl, PHP_URL_HOST) ?: $resolvedUrl),
                'url' => $resolvedUrl,
            ];
        }

        return $links;
    }
}

if (!function_exists('profile_social_links')) {
    function profile_social_links(?array $profile): array
    {
        if (!is_array($profile) || $profile === []) {
            return [];
        }

        $items = [
            'GitHub' => $profile['github_url'] ?? null,
            'LinkedIn' => $profile['linkedin_url'] ?? null,
            'Twitter/X' => $profile['twitter_url'] ?? null,
            'Instagram' => $profile['instagram_url'] ?? null,
            'WhatsApp' => $profile['whatsapp_url'] ?? null,
            'Facebook' => $profile['facebook_url'] ?? null,
        ];

        $links = [];
        foreach ($items as $label => $url) {
            $resolvedUrl = absolute_url($url);
            if ($resolvedUrl === null || filter_var($resolvedUrl, FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $links[] = [
                'label' => $label,
                'url' => $resolvedUrl,
            ];
        }

        return $links;
    }
}

if (!function_exists('social_platform_icon')) {
    function social_platform_icon(string $label, ?string $url = null): string
    {
        $label = strtolower(trim($label));
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $signature = $label . ' ' . $host . ' ' . strtolower((string) $url);

        return match (true) {
            str_contains($signature, 'github') => 'bi bi-github',
            str_contains($signature, 'linkedin') => 'bi bi-linkedin',
            str_contains($signature, 'twitter'), str_contains($signature, 'x.com') => 'bi bi-twitter-x',
            str_contains($signature, 'instagram') => 'bi bi-instagram',
            str_contains($signature, 'whatsapp'), str_contains($signature, 'wa.me') => 'bi bi-whatsapp',
            str_contains($signature, 'facebook'), str_contains($signature, 'fb.com') => 'bi bi-facebook',
            str_contains($signature, 'youtube') => 'bi bi-youtube',
            str_contains($signature, 'telegram') => 'bi bi-telegram',
            str_contains($signature, 'discord') => 'bi bi-discord',
            str_contains($signature, 'tiktok') => 'bi bi-tiktok',
            str_contains($signature, 'behance') => 'bi bi-behance',
            str_contains($signature, 'dribbble') => 'bi bi-dribbble',
            str_contains($signature, 'medium') => 'bi bi-medium',
            str_contains($signature, 'twitch') => 'bi bi-twitch',
            str_contains($signature, 'site'), str_contains($signature, 'web'), str_contains($signature, 'portfolio') => 'bi bi-globe2',
            default => 'bi bi-link-45deg',
        };
    }
}

if (!function_exists('social_platform_code')) {
    function social_platform_code(string $label): string
    {
        return match (strtolower(trim($label))) {
            'github' => 'GH',
            'linkedin' => 'IN',
            'twitter/x', 'twitter', 'x' => 'X',
            'instagram' => 'IG',
            'whatsapp' => 'WA',
            'facebook' => 'FB',
            default => strtoupper(substr((preg_replace('/[^A-Za-z]/', '', $label) ?: $label), 0, 2)),
        };
    }
}

if (!function_exists('presentation_video_data')) {
    function presentation_video_data(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $resolvedUrl = absolute_url($url);
        if ($resolvedUrl === null || filter_var($resolvedUrl, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        if ($youtubeId = extract_youtube_video_id($resolvedUrl)) {
            return [
                'type' => 'embed',
                'src' => 'https://www.youtube.com/embed/' . $youtubeId,
                'url' => $resolvedUrl,
            ];
        }

        if ($vimeoId = extract_vimeo_video_id($resolvedUrl)) {
            return [
                'type' => 'embed',
                'src' => 'https://player.vimeo.com/video/' . $vimeoId,
                'url' => $resolvedUrl,
            ];
        }

        if (is_direct_video_url($resolvedUrl)) {
            return [
                'type' => 'file',
                'src' => $resolvedUrl,
                'url' => $resolvedUrl,
            ];
        }

        return null;
    }
}

if (!function_exists('extract_youtube_video_id')) {
    function extract_youtube_video_id(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if ($host === 'youtu.be') {
            $candidate = strtok($path, '/');
            return is_valid_youtube_video_id($candidate) ? $candidate : null;
        }

        if (!in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            return null;
        }

        if ($path === 'watch') {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $candidate = (string) ($query['v'] ?? '');
            return is_valid_youtube_video_id($candidate) ? $candidate : null;
        }

        foreach (['shorts/', 'embed/', 'live/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $candidate = strtok(substr($path, strlen($prefix)), '/');
                return is_valid_youtube_video_id($candidate) ? $candidate : null;
            }
        }

        return null;
    }
}

if (!function_exists('is_valid_youtube_video_id')) {
    function is_valid_youtube_video_id(?string $value): bool
    {
        $value = (string) $value;
        return $value !== '' && preg_match('/^[A-Za-z0-9_-]{6,}$/', $value) === 1;
    }
}

if (!function_exists('extract_vimeo_video_id')) {
    function extract_vimeo_video_id(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        if (!in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            return null;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === '') {
            return null;
        }

        if (preg_match('/(?:video\/)?(\d+)/', $path, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}

if (!function_exists('is_direct_video_url')) {
    function is_direct_video_url(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $path = strtolower((string) ($parts['path'] ?? ''));
        if ($path === '') {
            return false;
        }

        return in_array(pathinfo($path, PATHINFO_EXTENSION), ['mp4', 'webm', 'ogg'], true);
    }
}

