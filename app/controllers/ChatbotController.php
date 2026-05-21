<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChatbotKnowledge;
use App\Services\ChatbotService;
use App\Services\RateLimiter;
use Throwable;

class ChatbotController extends Controller
{
    private const DEFAULT_CHATBOT_WINDOW_SECONDS = 300;
    private const DEFAULT_CHATBOT_MAX_MESSAGES = 24;

    public function message(): void
    {
        $retryAfter = $this->chatbotRetryAfter();
        if ($retryAfter > 0) {
            header('Retry-After: ' . $retryAfter);
            $this->json([
                'success' => false,
                'message' => 'Le chatbot est temporairement limité. Réessaie dans quelques instants.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        $data = $this->input();
        $message = trim((string) ($data['message'] ?? ''));
        $history = is_array($data['history'] ?? null) ? $data['history'] : [];
        $result = $this->resolveChatbotResult($message, $history);
        $this->hitChatbotRateLimit();

        $this->json([
            'success' => true,
            'answer' => (string) ($result['answer'] ?? $this->fallbackAnswer()),
            'source' => (string) ($result['source'] ?? 'local'),
            'remote_status' => (string) ($result['remote_status'] ?? 'local'),
            'remote_code' => $result['remote_code'] ?? null,
        ]);
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/chatbot', [
            'items' => (new ChatbotKnowledge())->all('created_at DESC'),
            'testQuestion' => flash('chatbot_question'),
            'testAnswer' => flash('chatbot_answer'),
            'testSource' => flash('chatbot_source'),
            'testRemoteStatus' => flash('chatbot_remote_status'),
            'testRemoteCode' => flash('chatbot_remote_code'),
        ], 'admin');
    }

    public function storeKnowledge(): void
    {
        $this->requireAdmin();
        if (!is_api_request()) {
            $this->validateCsrf();
        }

        $input = $this->input();
        $data = $this->payload($input);
        $errors = validate_required($data, ['question', 'answer']);
        if ($errors !== []) {
            $this->fail(reset($errors), is_api_request() ? '/api/v1/chatbot/knowledge' : '/admin/chatbot', 422, $errors);
        }

        $id = (new ChatbotKnowledge())->create($data);
        if (is_api_request()) {
            $this->json(['success' => true, 'id' => $id], 201);
        }

        flash('success', 'Connaissance ajoutée.');
        redirect('/admin/chatbot');
    }

    public function updateKnowledge(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new ChatbotKnowledge();
        $item = $model->find($id);
        if (!$item) {
            $this->fail('Connaissance introuvable.', '/admin/chatbot', 404);
        }

        $data = array_merge($item, $this->payload($_POST));
        $errors = validate_required($data, ['question', 'answer']);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/chatbot', 422, $errors);
        }

        $model->update($id, $data);
        flash('success', 'Connaissance mise à jour.');
        redirect('/admin/chatbot');
    }

    public function destroyKnowledge(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new ChatbotKnowledge();
        if (!$model->find($id)) {
            $this->fail('Connaissance introuvable.', '/admin/chatbot', 404);
        }

        $model->delete($id);
        flash('success', 'Connaissance supprimée.');
        redirect('/admin/chatbot');
    }

    public function testMessage(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $question = trim((string) ($_POST['message'] ?? ''));
        $result = $this->resolveChatbotResult($question);

        flash('chatbot_question', $question);
        flash('chatbot_answer', (string) ($result['answer'] ?? ''));
        flash('chatbot_source', (string) ($result['source'] ?? 'local'));
        flash('chatbot_remote_status', (string) ($result['remote_status'] ?? 'not_used'));
        flash('chatbot_remote_code', isset($result['remote_code']) ? (string) $result['remote_code'] : '');
        redirect('/admin/chatbot');
    }

    public function knowledgeApi(): void
    {
        $this->requireAdmin();
        $this->json(['success' => true, 'data' => (new ChatbotKnowledge())->all('created_at DESC')]);
    }

    private function payload(array $input): array
    {
        return [
            'question' => trim((string) ($input['question'] ?? '')),
            'answer' => trim((string) ($input['answer'] ?? '')),
            'keywords' => clean_nullable($input['keywords'] ?? ''),
            'is_active' => sanitize_bool($input['is_active'] ?? 1),
        ];
    }

    private function resolveChatbotResult(string $message, array $history = []): array
    {
        try {
            return (new ChatbotService())->answerResult($message, $history);
        } catch (Throwable $e) {
            $this->logChatbotFailure($e, [
                'message_length' => strlen($message),
                'history_items' => count($history),
            ]);

            return [
                'answer' => $this->fallbackAnswer(),
                'source' => 'fallback',
                'remote_status' => 'controller_error',
                'remote_code' => null,
            ];
        }
    }

    private function fallbackAnswer(): string
    {
        return 'Je rencontre un souci temporaire, mais tu peux toujours me demander le profil, les projets, les compétences, les certifications ou utiliser la page Contact.';
    }

    private function chatbotRetryAfter(): int
    {
        $key = $this->chatbotLimiterKey();
        $maxAttempts = $this->chatbotMaxMessages();
        $windowSeconds = $this->chatbotWindowSeconds();
        if (!RateLimiter::tooManyAttempts($key, $maxAttempts, $windowSeconds)) {
            return 0;
        }

        return RateLimiter::retryAfter($key, $maxAttempts, $windowSeconds);
    }

    private function hitChatbotRateLimit(): void
    {
        RateLimiter::hit($this->chatbotLimiterKey(), $this->chatbotWindowSeconds());
    }

    private function chatbotLimiterKey(): string
    {
        $parts = [];
        $sessionId = session_status() === PHP_SESSION_ACTIVE ? trim(session_id()) : '';
        if ($sessionId !== '') {
            $parts[] = 'sid:' . $sessionId;
        }

        $ip = $this->clientIp();
        if ($ip !== '') {
            $parts[] = 'ip:' . $ip;
        }

        $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($userAgent !== '') {
            $parts[] = 'ua:' . substr($userAgent, 0, 180);
        }

        if ($parts === []) {
            $parts[] = 'anonymous';
        }

        return 'chatbot:' . sha1(implode('|', $parts));
    }

    private function chatbotWindowSeconds(): int
    {
        return max(60, (int) env('CHATBOT_WINDOW_SECONDS', self::DEFAULT_CHATBOT_WINDOW_SECONDS));
    }

    private function chatbotMaxMessages(): int
    {
        return max(5, (int) env('CHATBOT_MAX_MESSAGES', self::DEFAULT_CHATBOT_MAX_MESSAGES));
    }

    private function clientIp(): string
    {
        $candidates = [];
        $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwardedFor !== '') {
            foreach (explode(',', $forwardedFor) as $entry) {
                $candidate = trim($entry);
                if ($candidate !== '') {
                    $candidates[] = $candidate;
                }
            }
        }

        foreach (['HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            $candidate = trim((string) ($_SERVER[$key] ?? ''));
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return substr($candidate, 0, 45);
            }
        }

        return '';
    }

    private function logChatbotFailure(Throwable $e, array $context = []): void
    {
        $directory = STORAGE_PATH . '/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = sprintf(
            "[%s] chatbot controller failure | message=%s | file=%s:%d | context=%s%s",
            date('c'),
            trim($e->getMessage()) !== '' ? trim($e->getMessage()) : 'unknown error',
            $e->getFile(),
            $e->getLine(),
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        @file_put_contents($directory . '/chatbot-api.log', $line, FILE_APPEND);
    }
}