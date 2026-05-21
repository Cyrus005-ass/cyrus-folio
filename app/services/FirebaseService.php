<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class FirebaseService
{
    private const AUTH_CERTIFICATES_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    private const DEFAULT_CERTIFICATES_TTL = 3600;
    private const FIRESTORE_SCOPE = 'https://www.googleapis.com/auth/datastore';
    private const STORAGE_SCOPE = 'https://www.googleapis.com/auth/devstorage.full_control';

    private static ?array $credentials = null;
    private static ?array $certificates = null;
    private static int $certificatesExpireAt = 0;
    private static array $accessTokens = [];

    public static function isEnabled(): bool
    {
        return (bool) env('FIREBASE_ENABLED', false) && self::projectId() !== '';
    }

    public static function adminApiEnabled(): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        try {
            self::credentials();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function projectId(): string
    {
        foreach (['FIREBASE_PROJECT_ID', 'GOOGLE_CLOUD_PROJECT', 'GCLOUD_PROJECT'] as $key) {
            $projectId = trim((string) env($key, ''));
            if ($projectId !== '') {
                return $projectId;
            }
        }

        if (self::credentialsPath() === null) {
            return '';
        }

        try {
            return trim((string) (self::credentials()['project_id'] ?? ''));
        } catch (Throwable) {
            return '';
        }
    }

    public static function storageBucket(): string
    {
        $bucket = trim((string) env('FIREBASE_STORAGE_BUCKET', ''));
        if ($bucket !== '') {
            return $bucket;
        }

        $projectId = self::projectId();
        return $projectId !== '' ? $projectId . '.appspot.com' : '';
    }

    public static function credentialsPath(): ?string
    {
        $paths = [];
        foreach (['FIREBASE_CREDENTIALS', 'GOOGLE_APPLICATION_CREDENTIALS'] as $key) {
            $path = trim((string) env($key, ''));
            if ($path !== '' && !in_array($path, $paths, true)) {
                $paths[] = $path;
            }
        }

        foreach (self::defaultCredentialsPaths() as $defaultPath) {
            if (!in_array($defaultPath, $paths, true)) {
                $paths[] = $defaultPath;
            }
        }

        foreach ($paths as $path) {
            $normalized = str_replace('\\', '/', $path);
            $candidates = [$normalized];

            if (!preg_match('/^[A-Za-z]:\//', $normalized) && !str_starts_with($normalized, '/')) {
                $candidates[] = rtrim(str_replace('\\', '/', BASE_PATH), '/') . '/' . ltrim($normalized, '/');
            }

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public static function verifyIdToken(string $idToken): array
    {
        if (!self::isEnabled()) {
            throw new RuntimeException('Firebase is disabled in the environment configuration.');
        }

        $projectId = self::projectId();
        if ($projectId === '') {
            throw new RuntimeException('Firebase project is not configured.');
        }

        $parts = explode('.', trim($idToken));
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid Firebase ID token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = self::decodeJwtSegment($encodedHeader);
        $payload = self::decodeJwtSegment($encodedPayload);
        $signature = self::base64UrlDecode($encodedSignature);

        if (($header['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Unsupported Firebase token algorithm.');
        }

        $kid = trim((string) ($header['kid'] ?? ''));
        if ($kid === '') {
            throw new RuntimeException('Firebase token key id is missing.');
        }

        $certificates = self::authCertificates();
        $certificate = $certificates[$kid] ?? null;
        if (!is_string($certificate) || trim($certificate) === '') {
            throw new RuntimeException('Unknown Firebase token signer.');
        }

        $verified = openssl_verify($encodedHeader . '.' . $encodedPayload, $signature, $certificate, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('Firebase token signature verification failed.');
        }

        self::assertIdTokenClaims($payload, $projectId);

        return $payload;
    }

    public static function issueAccessToken(array $scopes = [self::FIRESTORE_SCOPE]): string
    {
        if (!self::adminApiEnabled()) {
            throw new RuntimeException('Firebase admin credentials are not configured.');
        }

        $scopes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $scope): string => trim((string) $scope),
            $scopes
        ), static fn (string $scope): bool => $scope !== '')));

        if ($scopes === []) {
            throw new RuntimeException('At least one Google API scope is required.');
        }

        sort($scopes);
        $cacheKey = sha1(implode(' ', $scopes));
        $cached = self::$accessTokens[$cacheKey] ?? null;
        if (is_array($cached) && (($cached['expires_at'] ?? 0) > (time() + 30))) {
            return (string) $cached['token'];
        }

        $credentials = self::credentials();
        $tokenUri = trim((string) ($credentials['token_uri'] ?? ''));
        if ($tokenUri === '') {
            $tokenUri = 'https://oauth2.googleapis.com/token';
        }

        if (self::isAuthorizedUserCredentials($credentials)) {
            [$status, $response] = self::request(
                'POST',
                $tokenUri,
                ['Content-Type: application/x-www-form-urlencoded'],
                http_build_query([
                    'grant_type' => 'refresh_token',
                    'client_id' => (string) $credentials['client_id'],
                    'client_secret' => (string) $credentials['client_secret'],
                    'refresh_token' => (string) $credentials['refresh_token'],
                ], '', '&', PHP_QUERY_RFC3986)
            );

            $decoded = json_decode($response, true);
            if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['access_token'])) {
                $message = is_array($decoded) ? (string) ($decoded['error_description'] ?? $decoded['error'] ?? 'Unable to obtain a Google access token.') : 'Unable to obtain a Google access token.';
                throw new RuntimeException($message);
            }

            $ttl = max(300, (int) ($decoded['expires_in'] ?? 3600));
            self::$accessTokens[$cacheKey] = [
                'token' => (string) $decoded['access_token'],
                'expires_at' => time() + $ttl,
            ];

            return (string) self::$accessTokens[$cacheKey]['token'];
        }

        if (!self::isServiceAccountCredentials($credentials)) {
            throw new RuntimeException('Unsupported Google application credentials format.');
        }

        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
        $payload = self::base64UrlEncode(json_encode([
            'iss' => (string) $credentials['client_email'],
            'scope' => implode(' ', $scopes),
            'aud' => $tokenUri,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ], JSON_UNESCAPED_SLASHES));

        $signatureInput = $header . '.' . $payload;
        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Unable to read the Firebase private key.');
        }

        $signature = '';
        $signed = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($privateKey);
        }

        if (!$signed) {
            throw new RuntimeException('Unable to sign the Google OAuth assertion.');
        }

        $assertion = $signatureInput . '.' . self::base64UrlEncode($signature);
        [$status, $response] = self::request(
            'POST',
            $tokenUri,
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ], '', '&', PHP_QUERY_RFC3986)
        );

        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded) || empty($decoded['access_token'])) {
            $message = is_array($decoded) ? (string) ($decoded['error_description'] ?? $decoded['error'] ?? 'Unable to obtain a Google access token.') : 'Unable to obtain a Google access token.';
            throw new RuntimeException($message);
        }

        $ttl = max(300, (int) ($decoded['expires_in'] ?? 3600));
        self::$accessTokens[$cacheKey] = [
            'token' => (string) $decoded['access_token'],
            'expires_at' => time() + $ttl,
        ];

        return (string) self::$accessTokens[$cacheKey]['token'];
    }

    public static function firestoreDocumentUrl(string $documentPath = ''): string
    {
        if (!self::isEnabled()) {
            throw new RuntimeException('Firebase is disabled in the environment configuration.');
        }

        $projectId = self::projectId();
        if ($projectId === '') {
            throw new RuntimeException('Firebase project is not configured.');
        }

        $path = trim(str_replace('\\', '/', $documentPath), '/');
        $base = 'https://firestore.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/databases/(default)/documents';

        return $path === '' ? $base : $base . '/' . implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    public static function firestoreRequest(string $method, string $documentPath = '', ?array $payload = null, array $query = []): array
    {
        $url = self::firestoreDocumentUrl($documentPath);
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Authorization: Bearer ' . self::issueAccessToken([self::FIRESTORE_SCOPE]),
            'Accept: application/json',
        ];

        $body = null;
        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json; charset=utf-8';
        }

        [$status, $response] = self::request($method, $url, $headers, $body);
        $decoded = json_decode($response, true);

        return [
            'status' => $status,
            'data' => is_array($decoded) ? $decoded : null,
            'raw' => $response,
        ];
    }

    public static function firestoreFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            $fields[$key] = self::firestoreValue($value);
        }

        return $fields;
    }

    private static function credentials(): array
    {
        if (self::$credentials !== null) {
            return self::$credentials;
        }

        $path = self::credentialsPath();
        if ($path === null) {
            throw new RuntimeException('Google application credentials file not found.');
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid Google application credentials JSON file.');
        }

        if (self::isServiceAccountCredentials($decoded) || self::isAuthorizedUserCredentials($decoded)) {
            self::$credentials = $decoded;
            return self::$credentials;
        }

        throw new RuntimeException('Unsupported Google application credentials JSON file.');
    }

    private static function defaultCredentialsPaths(): array
    {
        $paths = [];
        $candidates = [];

        $appData = trim((string) (getenv('APPDATA') ?: ''));
        if ($appData !== '') {
            $candidates[] = $appData . '/gcloud/application_default_credentials.json';
        }

        $xdgConfigHome = trim((string) (getenv('XDG_CONFIG_HOME') ?: ''));
        if ($xdgConfigHome !== '') {
            $candidates[] = $xdgConfigHome . '/gcloud/application_default_credentials.json';
        }

        $home = trim((string) (getenv('HOME') ?: ''));
        if ($home !== '') {
            $candidates[] = $home . '/.config/gcloud/application_default_credentials.json';
        }

        $userProfile = trim((string) (getenv('USERPROFILE') ?: ''));
        if ($userProfile !== '') {
            $candidates[] = $userProfile . '/AppData/Roaming/gcloud/application_default_credentials.json';
            $candidates[] = $userProfile . '/.config/gcloud/application_default_credentials.json';
        }

        foreach ($candidates as $candidate) {
            $normalized = str_replace('\\', '/', trim($candidate));
            if ($normalized !== '' && !in_array($normalized, $paths, true)) {
                $paths[] = $normalized;
            }
        }

        return $paths;
    }

    private static function isServiceAccountCredentials(array $credentials): bool
    {
        if (trim((string) ($credentials['type'] ?? '')) !== 'service_account') {
            return false;
        }

        foreach (['project_id', 'private_key', 'client_email'] as $required) {
            if (trim((string) ($credentials[$required] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private static function isAuthorizedUserCredentials(array $credentials): bool
    {
        if (trim((string) ($credentials['type'] ?? '')) !== 'authorized_user') {
            return false;
        }

        foreach (['client_id', 'client_secret', 'refresh_token'] as $required) {
            if (trim((string) ($credentials[$required] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private static function authCertificates(): array
    {
        if (self::$certificates !== null && self::$certificatesExpireAt > time()) {
            return self::$certificates;
        }

        $cacheFile = rtrim(str_replace('\\', '/', STORAGE_PATH), '/') . '/cache/firebase/auth-certificates.json';
        $cacheDirectory = dirname($cacheFile);

        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && (($cached['expires_at'] ?? 0) > time()) && is_array($cached['certificates'] ?? null)) {
                self::$certificates = $cached['certificates'];
                self::$certificatesExpireAt = (int) $cached['expires_at'];
                return self::$certificates;
            }
        }

        [$status, $response] = self::request('GET', self::AUTH_CERTIFICATES_URL, ['Accept: application/json']);
        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded) || $decoded === []) {
            throw new RuntimeException('Unable to fetch Firebase public certificates.');
        }

        self::$certificates = $decoded;
        self::$certificatesExpireAt = time() + self::DEFAULT_CERTIFICATES_TTL;

        if (!is_dir($cacheDirectory)) {
            @mkdir($cacheDirectory, 0775, true);
        }

        @file_put_contents($cacheFile, json_encode([
            'expires_at' => self::$certificatesExpireAt,
            'certificates' => self::$certificates,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return self::$certificates;
    }

    private static function assertIdTokenClaims(array $claims, string $projectId): void
    {
        $issuer = 'https://securetoken.google.com/' . $projectId;
        $now = time();

        if (($claims['iss'] ?? '') !== $issuer) {
            throw new RuntimeException('Unexpected Firebase token issuer.');
        }

        if (($claims['aud'] ?? '') !== $projectId) {
            throw new RuntimeException('Unexpected Firebase token audience.');
        }

        $subject = trim((string) ($claims['sub'] ?? ''));
        if ($subject === '') {
            throw new RuntimeException('Firebase token subject is missing.');
        }

        $issuedAt = (int) ($claims['iat'] ?? 0);
        $expiresAt = (int) ($claims['exp'] ?? 0);
        if ($expiresAt <= 0 || $expiresAt <= ($now - 30)) {
            throw new RuntimeException('Firebase token has expired.');
        }

        if ($issuedAt > ($now + 300)) {
            throw new RuntimeException('Firebase token issue time is invalid.');
        }
    }

    private static function firestoreValue(mixed $value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            if (self::isListArray($value)) {
                return ['arrayValue' => ['values' => array_map([self::class, 'firestoreValue'], $value)]];
            }

            return ['mapValue' => ['fields' => self::firestoreFields($value)]];
        }

        if ($value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format(DATE_ATOM)];
        }

        return ['stringValue' => (string) $value];
    }

    private static function decodeJwtSegment(string $value): array
    {
        $decoded = self::base64UrlDecode($value);
        $json = json_decode($decoded, true);
        if (!is_array($json)) {
            throw new RuntimeException('Unable to decode the Firebase token.');
        }

        return $json;
    }

    private static function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url payload.');
        }

        return $decoded;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function isListArray(array $value): bool
    {
        $expectedKey = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expectedKey) {
                return false;
            }
            $expectedKey++;
        }

        return true;
    }

    private static function request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $timeout = max(5, (int) env('FIREBASE_TIMEOUT', 20));
        $headerLines = array_values(array_filter(array_map(
            static fn (mixed $header): string => trim((string) $header),
            $headers
        ), static fn (string $header): bool => $header !== ''));

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $options = [
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ];

            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            $caBundle = self::caBundlePath();
            if ($caBundle !== null) {
                $options[CURLOPT_CAINFO] = $caBundle;
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException($error !== '' ? $error : 'Firebase HTTP request failed.');
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return [$statusCode, (string) $response];
        }

        $sslOptions = [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ];

        $caBundle = self::caBundlePath();
        if ($caBundle !== null) {
            $sslOptions['cafile'] = $caBundle;
        }

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headerLines),
                'content' => $body ?? '',
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => $sslOptions,
        ]);

        $response = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $statusCode = 0;

        if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches) === 1) {
            $statusCode = (int) $matches[1];
        }

        if ($response === false) {
            $details = error_get_last();
            throw new RuntimeException((string) ($details['message'] ?? 'Firebase HTTP request failed.'));
        }

        return [$statusCode, (string) $response];
    }

    private static function caBundlePath(): ?string
    {
        $path = trim((string) env('FIREBASE_CA_BUNDLE', ''));
        if ($path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);
        if (is_file($normalized)) {
            return $normalized;
        }

        $basePathCandidate = rtrim(str_replace('\\', '/', BASE_PATH), '/') . '/' . ltrim($normalized, '/');
        return is_file($basePathCandidate) ? $basePathCandidate : null;
    }
}
