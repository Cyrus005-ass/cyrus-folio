<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;
use App\Services\ActivityService;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\MessageService;
use App\Services\NotificationService;
use App\Services\RateLimiter;

class ContactController extends Controller
{
    private const CONTACT_WINDOW_SECONDS = 900;
    private const CONTACT_MAX_SUBMISSIONS = 5;

    private Contact $model;

    public function __construct()
    {
        $this->model = new Contact();
    }

    public function store(): void
    {
        $this->validateCsrf();

        $retryAfter = $this->contactRetryAfter();
        if ($retryAfter > 0) {
            flash('error', 'Trop de messages envoyes depuis cette adresse. Reessaie dans quelques minutes.');
            redirect('/contact');
        }

        $data = $this->payload($_POST);
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/contact', 422, $errors);
        }

        $this->hitContactRateLimit();
        [$id, $message, $firestore] = $this->createArchivedMessage($data);
        NotificationService::push('contact', 'Nouveau message', $data['nom'] . ' a envoye un message.', '/admin/messages', 'contact:' . $id);
        ActivityService::log('contact.new', 'Nouveau message de ' . $data['nom']);
        $mailSent = $this->notifyByEmail($id, $data);

        flash('success', 'Message envoye. Merci !');
        $warnings = [];
        if (!$mailSent) {
            $warnings[] = 'Votre message a bien ete enregistre, mais la notification email n a pas pu etre envoyee.';
        }
        if ($warning = $this->firestoreWarning($firestore)) {
            $warnings[] = $warning;
        }
        if ($warnings !== []) {
            flash('warning', implode(' ', $warnings));
        }
        redirect('/contact');
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/messages', ['messages' => $this->model->all('created_at DESC')], 'admin');
    }

    public function show(string $id): void
    {
        $this->requireAdmin();
        $state = $this->messageState($id);
        $message = $state['message'];
        if (!$message) {
            $this->fail('Message introuvable.', '/admin/messages', 404);
        }

        if (($message['statut'] ?? 'nouveau') !== 'lu') {
            $liveReadResult = null;
            if ($state['archive'] !== null) {
                $this->model->update($id, ['statut' => 'lu']);
                $archiveMessage = $this->model->find($id) ?: array_merge($state['archive'], ['statut' => 'lu']);
                MessageService::syncContactMessage($archiveMessage);
            } elseif ($state['live'] !== null) {
                $liveReadResult = MessageService::markLiveMessageRead($id);
            }

            $state = $this->messageState($id);
            $message = $state['message']
                ?? (((bool) ($liveReadResult['synced'] ?? false)) ? array_merge($message, ['statut' => 'lu']) : $message);
        }

        $this->view('admin/message-show', ['message' => $message], 'admin');
    }

    public function markRead(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $state = $this->messageState($id);
        if ($state['archive'] !== null) {
            $this->model->update($id, ['statut' => 'lu']);
            $message = $this->model->find($id) ?: array_merge($state['archive'], ['statut' => 'lu']);
            $firestore = MessageService::syncContactMessage($message);
            flash('success', 'Message marque comme lu.');
            if ($warning = $this->firestoreWarning($firestore)) {
                flash('warning', $warning);
            }
            redirect('/admin/messages');
        }

        if ($state['live'] !== null) {
            $firestore = (($state['live']['statut'] ?? 'nouveau') !== 'lu')
                ? MessageService::markLiveMessageRead($id)
                : $this->successfulFirestoreResult($id);

            if (!(bool) ($firestore['synced'] ?? false)) {
                $this->fail($this->firestoreFailureMessage($firestore, 'Message introuvable.'), '/admin/messages', $this->firestoreFailureStatus($firestore));
            }

            flash('success', 'Message marque comme lu.');
            redirect('/admin/messages');
        }

        $this->fail('Message introuvable.', '/admin/messages', 404);
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $state = $this->messageState($id);
        if ($state['archive'] !== null) {
            $this->model->delete($id);
            $firestore = MessageService::deleteLiveMessage($id);
            flash('success', 'Message supprime.');
            if ($warning = $this->firestoreWarning($firestore, 'Le message local a ete supprime, mais la suppression Firestore a echoue.')) {
                flash('warning', $warning);
            }
            redirect('/admin/messages');
        }

        if ($state['live'] !== null) {
            $firestore = MessageService::deleteLiveMessage($id);
            if (!(bool) ($firestore['synced'] ?? false)) {
                $this->fail($this->firestoreFailureMessage($firestore, 'Impossible de supprimer le message live.'), '/admin/messages', $this->firestoreFailureStatus($firestore));
            }

            flash('success', 'Message supprime.');
            redirect('/admin/messages');
        }

        $this->fail('Message introuvable.', '/admin/messages', 404);
    }

    public function storeApi(): void
    {
        $retryAfter = $this->contactRetryAfter();
        if ($retryAfter > 0) {
            $this->json(['success' => false, 'message' => 'Trop de messages envoyes depuis cette adresse. Reessaie dans quelques minutes.'], 429);
        }

        $data = $this->payload($this->input());
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/contacts', 422, $errors);
        }

        $this->hitContactRateLimit();
        [$id, $message, $firestore] = $this->createArchivedMessage($data);
        NotificationService::push('contact', 'Nouveau message', $data['nom'] . ' a envoye un message.', '/admin/messages', 'contact:' . $id);
        ActivityService::log('contact.new', 'Nouveau message de ' . $data['nom']);
        $mailSent = $this->notifyByEmail($id, $data);

        $payload = [
            'success' => true,
            'id' => $id,
            'mail_sent' => $mailSent,
            'archive_source' => 'mysql',
            'firestore' => $firestore,
        ];
        if (!$mailSent) {
            $payload['warning'] = 'Le message a ete enregistre, mais la notification email n a pas pu etre envoyee.';
        }
        if ($warning = $this->firestoreWarning($firestore)) {
            $payload['warning'] = isset($payload['warning']) ? ($payload['warning'] . ' ' . $warning) : $warning;
        }

        $this->json($payload, 201);
    }

    public function indexApi(): void
    {
        $this->requireApiAdmin();
        $this->json(['success' => true, 'source' => 'mysql', 'data' => $this->model->all('created_at DESC')]);
    }

    public function liveIndexApi(): void
    {
        $this->requireApiAdmin();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $this->json($this->mergedMessagesPayload($limit));
    }

    public function liveShowApi(string $id): void
    {
        $this->requireApiAdmin();
        $archiveMessage = $this->normalizeArchiveMessage($this->model->find($id));
        $liveMessage = MessageService::syncEnabled() ? MessageService::findLiveMessage($id) : null;
        $message = $this->mergeMessagePair($archiveMessage, $liveMessage);
        if ($message === null) {
            $this->json(['success' => false, 'message' => 'Message introuvable.'], 404);
        }

        $this->json([
            'success' => true,
            'source' => (string) ($message['source'] ?? (MessageService::syncEnabled() ? 'merged' : 'mysql')),
            'live_enabled' => MessageService::syncEnabled(),
            'collection' => MessageService::collection(),
            'data' => $message,
        ]);
    }

    public function markReadApi(string $id): void
    {
        $this->requireApiAdmin();

        $state = $this->messageState($id);
        if ($state['archive'] !== null) {
            $this->model->update($id, ['statut' => 'lu']);
            $message = $this->model->find($id) ?: array_merge($state['archive'], ['statut' => 'lu']);
            $firestore = MessageService::syncContactMessage($message);

            $payload = [
                'success' => true,
                'message' => 'Message marque comme lu.',
                'data' => $message,
                'firestore' => $firestore,
            ];
            if ($warning = $this->firestoreWarning($firestore)) {
                $payload['warning'] = $warning;
            }

            $this->json($payload);
        }

        if ($state['live'] !== null) {
            $firestore = (($state['live']['statut'] ?? 'nouveau') !== 'lu')
                ? MessageService::markLiveMessageRead($id)
                : $this->successfulFirestoreResult($id);

            if (!(bool) ($firestore['synced'] ?? false)) {
                $this->json(['success' => false, 'message' => $this->firestoreFailureMessage($firestore, 'Message introuvable.')], $this->firestoreFailureStatus($firestore));
            }

            $freshState = $this->messageState($id);
            $message = $freshState['message'] ?? array_merge($state['live'], ['statut' => 'lu']);

            $this->json([
                'success' => true,
                'message' => 'Message marque comme lu.',
                'data' => $message,
                'firestore' => $firestore,
            ]);
        }

        $this->json(['success' => false, 'message' => 'Message introuvable.'], 404);
    }

    public function destroyApi(string $id): void
    {
        $this->requireApiAdmin();

        $state = $this->messageState($id);
        if ($state['archive'] !== null) {
            $this->model->delete($id);
            $firestore = MessageService::deleteLiveMessage($id);

            $payload = [
                'success' => true,
                'message' => 'Message supprime.',
                'id' => $id,
                'firestore' => $firestore,
            ];
            if ($warning = $this->firestoreWarning($firestore, 'Le message local a ete supprime, mais la suppression Firestore a echoue.')) {
                $payload['warning'] = $warning;
            }

            $this->json($payload);
        }

        if ($state['live'] !== null) {
            $firestore = MessageService::deleteLiveMessage($id);
            if (!(bool) ($firestore['synced'] ?? false)) {
                $this->json(['success' => false, 'message' => $this->firestoreFailureMessage($firestore, 'Impossible de supprimer le message live.')], $this->firestoreFailureStatus($firestore));
            }

            $this->json([
                'success' => true,
                'message' => 'Message supprime.',
                'id' => $id,
                'firestore' => $firestore,
            ]);
        }

        $this->json(['success' => false, 'message' => 'Message introuvable.'], 404);
    }

    private function notifyByEmail(int $id, array $data): bool
    {
        $mailSent = MailService::sendContactNotification($data);
        if (!$mailSent) {
            ActivityService::log('contact.mail_failed', 'Notification email non envoyee pour le message #' . $id);
        }

        return $mailSent;
    }

    private function payload(array $data): array
    {
        return [
            'nom' => trim((string) ($data['nom'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'sujet' => trim((string) ($data['sujet'] ?? '')),
            'message' => trim((string) ($data['message'] ?? '')),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'statut' => 'nouveau',
        ];
    }

    private function validatePayload(array $data): array
    {
        $errors = validate_required($data, ['nom', 'email', 'sujet', 'message']);
        if (!is_valid_email($data['email'] ?? null)) {
            $errors['email'] = 'Merci de fournir une adresse email valide.';
        }

        return $errors;
    }

    private function contactRetryAfter(): int
    {
        $key = 'contact:ip:' . substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        if (!RateLimiter::tooManyAttempts($key, self::CONTACT_MAX_SUBMISSIONS, self::CONTACT_WINDOW_SECONDS)) {
            return 0;
        }

        return RateLimiter::retryAfter($key, self::CONTACT_MAX_SUBMISSIONS, self::CONTACT_WINDOW_SECONDS);
    }

    private function hitContactRateLimit(): void
    {
        $key = 'contact:ip:' . substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        RateLimiter::hit($key, self::CONTACT_WINDOW_SECONDS);
    }

    private function createArchivedMessage(array $data): array
    {
        $id = $this->model->create($data);
        $message = $this->model->find($id) ?: array_merge($data, ['id' => $id]);
        $firestore = MessageService::syncContactMessage($message);

        return [$id, $message, $firestore];
    }

    private function firestoreWarning(array $firestore, string $message = 'Le message a bien ete archive, mais la synchro live Firestore a echoue.'): ?string
    {
        if (!(bool) ($firestore['enabled'] ?? false) || (bool) ($firestore['synced'] ?? false)) {
            return null;
        }

        return $message;
    }

    private function requireApiAdmin(): void
    {
        if (is_api_request() && !auth_check()) {
            $bearerToken = request_bearer_token();
            if ($bearerToken !== null) {
                AuthService::loginWithFirebaseIdToken($bearerToken);
            }
        }

        $this->requireAdmin();
    }

    private function messageState(string $id): array
    {
        $id = trim($id);
        $archiveMessage = $id !== '' ? $this->normalizeArchiveMessage($this->model->find($id)) : null;
        $liveMessage = ($id !== '' && MessageService::syncEnabled()) ? MessageService::findLiveMessage($id) : null;

        return [
            'archive' => $archiveMessage,
            'live' => $liveMessage,
            'message' => $this->mergeMessagePair($archiveMessage, $liveMessage),
        ];
    }

    private function successfulFirestoreResult(string $messageId): array
    {
        return [
            'enabled' => true,
            'synced' => true,
            'document_id' => trim($messageId) !== '' ? trim($messageId) : null,
            'collection' => MessageService::collection(),
            'error' => null,
        ];
    }

    private function firestoreFailureMessage(array $firestore, string $default): string
    {
        $error = trim((string) ($firestore['error'] ?? ''));
        return $error !== '' ? $error : $default;
    }

    private function firestoreFailureStatus(array $firestore, int $default = 422): int
    {
        $error = strtolower(trim((string) ($firestore['error'] ?? '')));
        return str_contains($error, 'introuvable') ? 404 : $default;
    }

    private function mergedMessagesPayload(int $limit): array
    {
        $archiveMessages = $this->archiveMessages();
        $liveEnabled = MessageService::syncEnabled();
        $liveMessages = $liveEnabled ? MessageService::listLiveMessages($limit) : [];
        $mergedMessages = $this->mergeMessages($archiveMessages, $liveMessages, $limit);

        return [
            'success' => true,
            'source' => $liveEnabled ? 'merged' : 'mysql',
            'live_enabled' => $liveEnabled,
            'collection' => MessageService::collection(),
            'archive_count' => count($archiveMessages),
            'live_count' => count($liveMessages),
            'last_sync_at' => date(DATE_ATOM),
            'data' => $mergedMessages,
        ];
    }

    private function archiveMessages(): array
    {
        return array_values(array_filter(array_map(fn (array $message): ?array => $this->normalizeArchiveMessage($message), $this->model->all('created_at DESC'))));
    }

    private function normalizeArchiveMessage(?array $message): ?array
    {
        if (!is_array($message) || $message === []) {
            return null;
        }

        $message['id'] = trim((string) ($message['id'] ?? ''));
        $message['source'] = 'mysql';
        $message['live_synced'] = false;
        $message['live_available'] = false;
        $message['is_live'] = false;

        return $message;
    }

    private function mergeMessages(array $archiveMessages, array $liveMessages, int $limit): array
    {
        $mergedById = [];

        foreach ($archiveMessages as $message) {
            $id = trim((string) ($message['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $mergedById[$id] = $message;
        }

        foreach ($liveMessages as $message) {
            $id = trim((string) ($message['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $mergedById[$id] = $this->mergeMessagePair($mergedById[$id] ?? null, $message);
        }

        $messages = array_values(array_filter($mergedById));
        usort($messages, fn (array $left, array $right): int => $this->messageSortTimestamp($right) <=> $this->messageSortTimestamp($left));

        return array_slice($messages, 0, $limit);
    }

    private function mergeMessagePair(?array $archiveMessage, ?array $liveMessage): ?array
    {
        if ($archiveMessage === null && $liveMessage === null) {
            return null;
        }

        if ($archiveMessage === null) {
            $liveMessage['source'] = 'firestore';
            $liveMessage['live_synced'] = true;
            $liveMessage['live_available'] = true;
            $liveMessage['is_live'] = true;
            return $liveMessage;
        }

        if ($liveMessage === null) {
            return $archiveMessage;
        }

        return array_merge($archiveMessage, $liveMessage, [
            'source' => 'merged',
            'live_synced' => true,
            'live_available' => true,
            'is_live' => true,
        ]);
    }

    private function messageSortTimestamp(array $message): int
    {
        $createdAt = trim((string) ($message['created_at'] ?? ''));
        $timestamp = strtotime($createdAt);
        return $timestamp !== false ? $timestamp : 0;
    }
}
