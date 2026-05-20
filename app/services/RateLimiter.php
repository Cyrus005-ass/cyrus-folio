<?php

namespace App\Services;

class RateLimiter
{
    public static function hit(string $key, int $windowSeconds = 900): int
    {
        $attempts = self::read($key, $windowSeconds);
        $attempts[] = time();
        self::write($key, $attempts);

        return count($attempts);
    }

    public static function attempts(string $key, int $windowSeconds = 900): int
    {
        return count(self::read($key, $windowSeconds));
    }

    public static function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds = 900): bool
    {
        return self::attempts($key, $windowSeconds) >= max(1, $maxAttempts);
    }

    public static function retryAfter(string $key, int $maxAttempts, int $windowSeconds = 900): int
    {
        $attempts = self::read($key, $windowSeconds);
        $maxAttempts = max(1, $maxAttempts);
        if (count($attempts) < $maxAttempts) {
            return 0;
        }

        $index = max(0, count($attempts) - $maxAttempts);
        $unlockAt = ((int) $attempts[$index]) + max(1, $windowSeconds);

        return max(1, $unlockAt - time());
    }

    public static function clear(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function read(string $key, int $windowSeconds): array
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return [];
        }

        $content = @file_get_contents($path);
        $decoded = json_decode(is_string($content) ? $content : '[]', true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::prune($decoded, $windowSeconds);
    }

    private static function write(string $key, array $attempts): void
    {
        $path = self::path($key);
        $attempts = array_values(array_map('intval', $attempts));

        if ($attempts === []) {
            if (is_file($path)) {
                @unlink($path);
            }
            return;
        }

        @file_put_contents($path, json_encode($attempts, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private static function prune(array $attempts, int $windowSeconds): array
    {
        $cutoff = time() - max(1, $windowSeconds);

        return array_values(array_filter(
            array_map('intval', $attempts),
            static fn (int $timestamp): bool => $timestamp > $cutoff
        ));
    }

    private static function path(string $key): string
    {
        $directory = STORAGE_PATH . '/cache/rate-limiter';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory . '/' . sha1($key) . '.json';
    }
}