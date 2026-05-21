<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class MessageService
{
    private const DEFAULT_COLLECTION = 'messages';

    public static function syncEnabled(): bool
    {
        return FirebaseService::adminApiEnabled() && (bool) env('FIREBASE_MESSAGES_SYNC', true);
    }

    public static function collection(): string
    {
        $collection = trim((string) env('FIREBASE_MESSAGES_COLLECTION', self::DEFAULT_COLLECTION));
        return $collection !== '' ? $collection : self::DEFAULT_COLLECTION;
    }

    public static function syncContactMessage(array $message): array
    {
        $messageId = trim((string) ($message['id'] ?? ''));
        if ($messageId === '') {
            return self::result(false, false, null, 'Impossible de synchroniser un message sans identifiant.');
        }

        if (!self::syncEnabled()) {
            return self::result(false, false, $messageId, null);
        }

        try {
            $response = FirebaseService::firestoreRequest('PATCH', self::documentPath($messageId), [
                'fields' => FirebaseService::firestoreFields(self::livePayload($message)),
            ]);

            if (($response['status'] ?? 500) < 200 || ($response['status'] ?? 500) >= 300) {
                throw new RuntimeException(self::responseError($response, 'La synchro Firestore du message a ?chou?.'));
            }

            return self::result(true, true, $messageId, null);
        } catch (Throwable $e) {
            ActivityService::log('firebase.messages.sync_failed', '?chec de synchro Firestore pour le message #' . $messageId . ' : ' . $e->getMessage());
            return self::result(true, false, $messageId, $e->getMessage());
        }
    }

    public static function markLiveMessageRead(int|string $messageId): array
    {
        $messageId = trim((string) $messageId);
        if ($messageId === '') {
            return self::result(false, false, null, 'Impossible de marquer comme lu un message live sans identifiant.');
        }

        if (!self::syncEnabled()) {
            return self::result(false, false, $messageId, null);
        }

        $message = self::findLiveMessage($messageId);
        if ($message === null) {
            return self::result(true, false, $messageId, 'Message live introuvable.');
        }

        $message['statut'] = 'lu';
        $message['updated_at'] = date(DATE_ATOM);
        $message['synced_at'] = date(DATE_ATOM);
        $message['source'] = trim((string) ($message['source'] ?? 'firestore')) ?: 'firestore';

        return self::syncContactMessage($message);
    }

    public static function deleteLiveMessage(int|string $messageId): array
    {
        $messageId = trim((string) $messageId);
        if ($messageId === '') {
            return self::result(false, false, null, 'Impossible de supprimer un message live sans identifiant.');
        }

        if (!self::syncEnabled()) {
            return self::result(false, false, $messageId, null);
        }

        try {
            $response = FirebaseService::firestoreRequest('DELETE', self::documentPath($messageId));
            $status = (int) ($response['status'] ?? 500);
            if ($status === 404) {
                return self::result(true, true, $messageId, null);
            }

            if ($status < 200 || $status >= 300) {
                throw new RuntimeException(self::responseError($response, 'La suppression Firestore du message a ?chou?.'));
            }

            return self::result(true, true, $messageId, null);
        } catch (Throwable $e) {
            ActivityService::log('firebase.messages.delete_failed', '?chec de suppression Firestore pour le message #' . $messageId . ' : ' . $e->getMessage());
            return self::result(true, false, $messageId, $e->getMessage());
        }
    }

    public static function listLiveMessages(int $limit = 50): array
    {
        if (!self::syncEnabled()) {
            return [];
        }

        $limit = max(1, min(100, $limit));

        try {
            $response = FirebaseService::firestoreRequest('GET', self::collection(), null, [
                'pageSize' => $limit,
            ]);

            if (($response['status'] ?? 500) < 200 || ($response['status'] ?? 500) >= 300) {
                throw new RuntimeException(self::responseError($response, 'Impossible de r?cup?rer les messages live.'));
            }

            $documents = $response['data']['documents'] ?? [];
            if (!is_array($documents)) {
                return [];
            }

            $messages = array_map([self::class, 'mapDocument'], $documents);
            usort($messages, static function (array $left, array $right): int {
                $leftTimestamp = strtotime((string) ($left['created_at'] ?? '')) ?: 0;
                $rightTimestamp = strtotime((string) ($right['created_at'] ?? '')) ?: 0;
                return $rightTimestamp <=> $leftTimestamp;
            });

            return $messages;
        } catch (Throwable $e) {
            ActivityService::log('firebase.messages.fetch_failed', '?chec de lecture Firestore des messages : ' . $e->getMessage());
            return [];
        }
    }

    public static function findLiveMessage(int|string $messageId): ?array
    {
        $messageId = trim((string) $messageId);
        if ($messageId === '' || !self::syncEnabled()) {
            return null;
        }

        try {
            $response = FirebaseService::firestoreRequest('GET', self::documentPath($messageId));
            $status = (int) ($response['status'] ?? 500);
            if ($status === 404) {
                return null;
            }

            if ($status < 200 || $status >= 300 || !is_array($response['data'] ?? null)) {
                throw new RuntimeException(self::responseError($response, 'Impossible de r?cup?rer le message live.'));
            }

            return self::mapDocument($response['data']);
        } catch (Throwable $e) {
            ActivityService::log('firebase.messages.find_failed', '?chec de lecture Firestore pour le message #' . $messageId . ' : ' . $e->getMessage());
            return null;
        }
    }

    private static function livePayload(array $message): array
    {
        $createdAt = self::normalizeTimestamp($message['created_at'] ?? null);
        $updatedAt = self::normalizeTimestamp($message['updated_at'] ?? ($message['created_at'] ?? null));

        return [
            'id' => trim((string) ($message['id'] ?? '')),
            'nom' => trim((string) ($message['nom'] ?? '')),
            'email' => trim((string) ($message['email'] ?? '')),
            'sujet' => trim((string) ($message['sujet'] ?? '')),
            'message' => trim((string) ($message['message'] ?? '')),
            'statut' => trim((string) ($message['statut'] ?? 'nouveau')) ?: 'nouveau',
            'ip_address' => clean_nullable($message['ip_address'] ?? null),
            'user_agent' => clean_nullable($message['user_agent'] ?? null),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'source' => trim((string) ($message['source'] ?? 'mysql')) ?: 'mysql',
            'synced_at' => date(DATE_ATOM),
            'is_live' => true,
        ];
    }

    private static function mapDocument(array $document): array
    {
        $fields = self::decodeFields($document['fields'] ?? []);
        $documentId = self::documentIdFromName((string) ($document['name'] ?? ''));

        return [
            'id' => trim((string) ($fields['id'] ?? $documentId)),
            'firestore_id' => $documentId,
            'nom' => trim((string) ($fields['nom'] ?? '')),
            'email' => trim((string) ($fields['email'] ?? '')),
            'sujet' => trim((string) ($fields['sujet'] ?? '')),
            'message' => trim((string) ($fields['message'] ?? '')),
            'statut' => trim((string) ($fields['statut'] ?? 'nouveau')) ?: 'nouveau',
            'ip_address' => clean_nullable($fields['ip_address'] ?? null),
            'user_agent' => clean_nullable($fields['user_agent'] ?? null),
            'created_at' => trim((string) ($fields['created_at'] ?? self::normalizeTimestamp($document['createTime'] ?? null))),
            'updated_at' => trim((string) ($fields['updated_at'] ?? self::normalizeTimestamp($document['updateTime'] ?? null))),
            'source' => trim((string) ($fields['source'] ?? 'firebase')),
            'is_live' => true,
            'synced_at' => trim((string) ($fields['synced_at'] ?? '')),
        ];
    }

    private static function decodeFields(array $fields): array
    {
        $decoded = [];
        foreach ($fields as $key => $value) {
            $decoded[(string) $key] = self::decodeValue(is_array($value) ? $value : []);
        }

        return $decoded;
    }

    private static function decodeValue(array $value): mixed
    {
        if (array_key_exists('nullValue', $value)) {
            return null;
        }
        if (array_key_exists('stringValue', $value)) {
            return (string) $value['stringValue'];
        }
        if (array_key_exists('integerValue', $value)) {
            return (string) $value['integerValue'];
        }
        if (array_key_exists('doubleValue', $value)) {
            return (float) $value['doubleValue'];
        }
        if (array_key_exists('booleanValue', $value)) {
            return (bool) $value['booleanValue'];
        }
        if (array_key_exists('timestampValue', $value)) {
            return (string) $value['timestampValue'];
        }
        if (isset($value['mapValue']['fields']) && is_array($value['mapValue']['fields'])) {
            return self::decodeFields($value['mapValue']['fields']);
        }
        if (isset($value['arrayValue']['values']) && is_array($value['arrayValue']['values'])) {
            return array_map([self::class, 'decodeValue'], $value['arrayValue']['values']);
        }

        return null;
    }

    private static function normalizeTimestamp(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return date(DATE_ATOM);
        }

        $timestamp = strtotime($text);
        return $timestamp !== false ? date(DATE_ATOM, $timestamp) : date(DATE_ATOM);
    }

    private static function documentIdFromName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $name), static fn (string $segment): bool => $segment !== ''));
        return $segments === [] ? '' : (string) end($segments);
    }

    private static function documentPath(string $messageId): string
    {
        return self::collection() . '/' . trim($messageId);
    }

    private static function responseError(array $response, string $default): string
    {
        $data = $response['data'] ?? null;
        if (is_array($data)) {
            $error = $data['error'] ?? null;
            if (is_array($error)) {
                $message = trim((string) ($error['message'] ?? ''));
                if ($message !== '') {
                    return $message;
                }
            }
        }

        return $default;
    }

    private static function result(bool $enabled, bool $synced, ?string $messageId, ?string $error): array
    {
        return [
            'enabled' => $enabled,
            'synced' => $synced,
            'document_id' => $messageId,
            'collection' => self::collection(),
            'error' => $error,
        ];
    }
}
