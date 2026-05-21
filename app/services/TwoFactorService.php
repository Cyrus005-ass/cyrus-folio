<?php

namespace App\Services;

class TwoFactorService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const CODE_DIGITS = 6;
    private const TIME_STEP = 30;
    private const DEFAULT_WINDOW = 1;
    private const CODE_MODULO = 1000000;

    public static function enabledForUser(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        return (int) ($user['two_factor_enabled'] ?? 0) === 1
            && self::normalizeSecret((string) ($user['two_factor_secret'] ?? '')) !== '';
    }

    public static function issuer(): string
    {
        $issuer = trim((string) env('TWO_FACTOR_ISSUER', env('APP_NAME', 'Portfolio OS')));
        return $issuer !== '' ? $issuer : 'Portfolio OS';
    }

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes(max(10, $bytes)));
    }

    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split(self::normalizeSecret($secret), 4, ' '));
    }

    public static function maskSecret(string $secret): string
    {
        $groups = str_split(self::normalizeSecret($secret), 4);
        if ($groups === []) {
            return '';
        }

        if (count($groups) <= 2) {
            return implode(' ', $groups);
        }

        foreach ($groups as $index => $group) {
            if ($index > 0 && $index < count($groups) - 1) {
                $groups[$index] = '****';
            }
        }

        return implode(' ', $groups);
    }

    public static function provisioningUri(string $secret, string $accountLabel): string
    {
        $label = trim($accountLabel) !== '' ? trim($accountLabel) : 'admin';
        $issuer = self::issuer();

        return 'otpauth://totp/'
            . rawurlencode($issuer . ':' . $label)
            . '?secret=' . rawurlencode(self::normalizeSecret($secret))
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1'
            . '&digits=' . self::CODE_DIGITS
            . '&period=' . self::TIME_STEP;
    }

    public static function isValidSecret(string $secret): bool
    {
        return self::base32Decode($secret) !== null;
    }

    public static function normalizeCode(string $code): string
    {
        return preg_replace('/\D+/', '', trim($code)) ?? '';
    }

    public static function verifyCode(string $secret, string $code, int $window = self::DEFAULT_WINDOW): bool
    {
        $normalizedCode = self::normalizeCode($code);
        if (strlen($normalizedCode) !== self::CODE_DIGITS) {
            return false;
        }

        $window = max(0, $window);
        $timestamp = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = self::codeAt($secret, $timestamp + ($offset * self::TIME_STEP));
            if ($candidate !== '' && hash_equals($candidate, $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public static function codeAt(string $secret, ?int $timestamp = null): string
    {
        $decodedSecret = self::base32Decode($secret);
        if ($decodedSecret === null) {
            return '';
        }

        $counter = intdiv(max(0, (int) ($timestamp ?? time())), self::TIME_STEP);
        $binaryCounter = pack('N2', intdiv($counter, 0x100000000), $counter % 0x100000000);
        $hash = hash_hmac('sha1', $binaryCounter, $decodedSecret, true);
        if (!is_string($hash) || strlen($hash) < 20) {
            return '';
        }

        $offset = ord(substr($hash, -1)) & 0x0F;
        $chunk = substr($hash, $offset, 4);
        if ($chunk === false || strlen($chunk) !== 4) {
            return '';
        }

        $value = unpack('N', $chunk)[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % self::CODE_MODULO), self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    private static function normalizeSecret(string $secret): string
    {
        return strtoupper(preg_replace('/[^A-Z2-7]/', '', trim($secret)) ?? '');
    }

    private static function base32Encode(string $binary): string
    {
        if ($binary === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($binary) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if ($chunk === '') {
                continue;
            }

            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private static function base32Decode(string $secret): ?string
    {
        $secret = self::normalizeSecret($secret);
        if ($secret === '') {
            return null;
        }

        $alphabetMap = array_flip(str_split(self::ALPHABET));
        $bits = '';

        foreach (str_split($secret) as $character) {
            if (!array_key_exists($character, $alphabetMap)) {
                return null;
            }

            $bits .= str_pad(decbin((int) $alphabetMap[$character]), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded !== '' ? $decoded : null;
    }
}