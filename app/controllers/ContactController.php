<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contact;
use App\Services\ActivityService;
use App\Services\MailService;
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
        $id = $this->model->create($data);
        NotificationService::push('contact', 'Nouveau message', $data['nom'] . ' a envoye un message.', '/admin/messages', 'contact:' . $id);
        ActivityService::log('contact.new', 'Nouveau message de ' . $data['nom']);
        $mailSent = $this->notifyByEmail($id, $data);

        flash('success', 'Message envoye. Merci !');
        if (!$mailSent) {
            flash('warning', 'Votre message a bien ete enregistre, mais la notification email n a pas pu etre envoyee.');
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
        $message = $this->model->find($id);
        if (!$message) {
            $this->fail('Message introuvable.', '/admin/messages', 404);
        }

        if (($message['statut'] ?? 'nouveau') !== 'lu') {
            $this->model->update($id, ['statut' => 'lu']);
            $message['statut'] = 'lu';
        }

        $this->view('admin/message-show', ['message' => $message], 'admin');
    }

    public function markRead(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Message introuvable.', '/admin/messages', 404);
        }

        $this->model->update($id, ['statut' => 'lu']);
        flash('success', 'Message marque comme lu.');
        redirect('/admin/messages');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Message introuvable.', '/admin/messages', 404);
        }

        $this->model->delete($id);
        flash('success', 'Message supprime.');
        redirect('/admin/messages');
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
        $id = $this->model->create($data);
        NotificationService::push('contact', 'Nouveau message', $data['nom'] . ' a envoye un message.', '/admin/messages', 'contact:' . $id);
        ActivityService::log('contact.new', 'Nouveau message de ' . $data['nom']);
        $mailSent = $this->notifyByEmail($id, $data);

        $payload = ['success' => true, 'id' => $id, 'mail_sent' => $mailSent];
        if (!$mailSent) {
            $payload['warning'] = 'Le message a ete enregistre, mais la notification email n a pas pu etre envoyee.';
        }

        $this->json($payload, 201);
    }

    public function indexApi(): void
    {
        $this->requireAdmin();
        $this->json(['success' => true, 'data' => $this->model->all('created_at DESC')]);
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
}