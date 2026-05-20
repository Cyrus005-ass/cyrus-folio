<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ChatbotKnowledge;
use App\Services\ChatbotService;
use App\Services\RateLimiter;

class ChatbotController extends Controller
{
    private const CHATBOT_WINDOW_SECONDS = 300;
    private const CHATBOT_MAX_MESSAGES = 24;

    public function message(): void
    {
        $retryAfter = $this->chatbotRetryAfter();
        if ($retryAfter > 0) {
            $this->json([
                'success' => false,
                'message' => 'Le chatbot est temporairement limite. Reessaie dans quelques instants.',
            ], 429);
        }

        $data = $this->input();
        $history = is_array($data['history'] ?? null) ? $data['history'] : [];
        $result = (new ChatbotService())->answerResult((string) ($data['message'] ?? ''), $history);
        $this->hitChatbotRateLimit();

        $this->json([
            'success' => true,
            'answer' => $result['answer'],
            'source' => $result['source'] ?? 'local',
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

        flash('success', 'Connaissance ajoutee.');
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
        flash('success', 'Connaissance mise a jour.');
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
        flash('success', 'Connaissance supprimee.');
        redirect('/admin/chatbot');
    }

    public function testMessage(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $question = trim((string) ($_POST['message'] ?? ''));
        $result = (new ChatbotService())->answerResult($question);

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

    private function chatbotRetryAfter(): int
    {
        $key = 'chatbot:ip:' . substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        if (!RateLimiter::tooManyAttempts($key, self::CHATBOT_MAX_MESSAGES, self::CHATBOT_WINDOW_SECONDS)) {
            return 0;
        }

        return RateLimiter::retryAfter($key, self::CHATBOT_MAX_MESSAGES, self::CHATBOT_WINDOW_SECONDS);
    }

    private function hitChatbotRateLimit(): void
    {
        $key = 'chatbot:ip:' . substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        RateLimiter::hit($key, self::CHATBOT_WINDOW_SECONDS);
    }
}