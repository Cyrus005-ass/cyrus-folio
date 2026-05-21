<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\ChatbotKnowledge;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use RuntimeException;
use Throwable;

class ChatbotService
{
    public function answer(string $message, array $history = []): string
    {
        return $this->answerResult($message, $history)['answer'];
    }

    public function answerResult(string $message, array $history = []): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'answer' => 'Pose-moi une question sur le profil, les projets, les compétences, les certifications ou le contact.',
                'source' => 'local',
                'remote_status' => 'empty_message',
                'remote_code' => null,
            ];
        }

        $remote = $this->answerWithGroq($message, $history);
        if (!empty($remote['answer'])) {
            return [
                'answer' => (string) $remote['answer'],
                'source' => 'groq',
                'remote_status' => (string) ($remote['status'] ?? 'ok'),
                'remote_code' => $remote['status_code'] ?? null,
            ];
        }

        return [
            'answer' => $this->answerLocally($message),
            'source' => 'local',
            'remote_status' => (string) ($remote['status'] ?? 'not_used'),
            'remote_code' => $remote['status_code'] ?? null,
        ];
    }

    private function answerWithGroq(string $message, array $history = []): array
    {
        $apiKey = trim((string) env('GROQ_API_KEY', env('XAI_API_KEY', '')));
        if ($apiKey === '') {
            return ['answer' => null, 'status' => 'disabled', 'status_code' => null];
        }

        $requests = $this->buildRemoteRequests($message, $history);
        $lastFailure = ['answer' => null, 'status' => 'unavailable', 'status_code' => null];

        foreach ($requests as $request) {
            $body = json_encode($request['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($body) || $body === '') {
                $lastFailure = ['answer' => null, 'status' => 'invalid_payload', 'status_code' => null];
                continue;
            }

            try {
                [$statusCode, $responseBody] = $this->postJson(
                    $request['url'],
                    [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $apiKey,
                        'User-Agent: portfolio-os-chatbot',
                    ],
                    $body
                );
            } catch (Throwable $e) {
                $lastFailure = [
                    'answer' => null,
                    'status' => 'transport_error',
                    'status_code' => null,
                    'error' => $e->getMessage(),
                ];
                $this->logRemoteFailure($request['url'], $lastFailure, null);
                continue;
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                $lastFailure = [
                    'answer' => null,
                    'status' => $this->mapRemoteStatus($statusCode),
                    'status_code' => $statusCode,
                ];
                $this->logRemoteFailure($request['url'], $lastFailure, $responseBody);
                if (!$this->shouldTryNextRequest($statusCode)) {
                    return $lastFailure;
                }
                continue;
            }

            $response = json_decode($responseBody, true);
            if (!is_array($response)) {
                $lastFailure = ['answer' => null, 'status' => 'invalid_json', 'status_code' => $statusCode];
                $this->logRemoteFailure($request['url'], $lastFailure, $responseBody);
                continue;
            }

            $answer = $request['kind'] === 'chat_completions'
                ? $this->extractChatCompletionAnswer($response)
                : $this->extractRemoteAnswer($response);

            if (is_string($answer) && trim($answer) !== '') {
                return ['answer' => trim($answer), 'status' => 'ok', 'status_code' => $statusCode];
            }

            $lastFailure = ['answer' => null, 'status' => 'empty_response', 'status_code' => $statusCode];
            $this->logRemoteFailure($request['url'], $lastFailure, $responseBody);
        }

        return $lastFailure;
    }

    private function buildRemoteRequests(string $message, array $history): array
    {
        $model = (string) env('GROQ_MODEL', env('XAI_MODEL', 'llama-3.3-70b-versatile'));
        $primaryUrl = $this->normalizeRemoteUrl(trim((string) env('GROQ_API_URL', env('XAI_API_URL', 'https://api.groq.com/openai/v1/chat/completions'))));
        $prompt = $this->buildRemotePrompt($message, $history);
        $systemMessage = 'You are the assistant for a developer portfolio website. Answer in French unless the user clearly uses another language. Use only the portfolio data provided. If information is missing, say so clearly and suggest the contact page.';

        $requests = [];
        $primaryKind = str_contains($primaryUrl, '/responses') ? 'responses' : 'chat_completions';

        $requests[] = [
            'kind' => $primaryKind,
            'url' => $primaryUrl,
            'payload' => $primaryKind === 'chat_completions'
                ? [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]
                : [
                    'model' => $model,
                    'instructions' => $systemMessage,
                    'input' => $prompt,
                ],
        ];

        if ($primaryKind !== 'chat_completions') {
            $requests[] = [
                'kind' => 'chat_completions',
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'payload' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemMessage],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ];
        }

        return $requests;
    }

    private function answerLocally(string $message): string
    {
        $normalizedMessage = $this->normalize($message);
        $profile = $this->tryOrDefault(fn () => (new Profile())->current(), null);
        $projects = $this->tryOrDefault(fn () => array_slice((new Project())->publicAll(), 0, 5), []);
        $skills = $this->tryOrDefault(fn () => array_slice((new Skill())->active(), 0, 8), []);
        $certifications = $this->tryOrDefault(fn () => array_slice((new Certification())->active(), 0, 5), []);

        $knowledgeAnswer = $this->findKnowledgeAnswer($normalizedMessage);
        if ($knowledgeAnswer !== null) {
            return $knowledgeAnswer;
        }

        if ($this->containsAny($normalizedMessage, ['qui es tu', 'qui est tu', 'presente toi', 'presente-toi', 'parle de toi', 'profil', 'parcours', 'bio', 'a propos'])) {
            return $this->buildProfileAnswer($profile);
        }

        if ($this->containsAny($normalizedMessage, ['projet', 'portfolio', 'realisation', 'realisations', 'travail', 'travaux', 'demo', 'application'])) {
            return $this->buildProjectsAnswer($projects);
        }

        if ($this->containsAny($normalizedMessage, ['competenc', 'skill', 'stack', 'technolog', 'framework', 'outil', 'langage'])) {
            return $this->buildSkillsAnswer($skills);
        }

        if ($this->containsAny($normalizedMessage, ['certif', 'badge', 'formation'])) {
            return $this->buildCertificationsAnswer($certifications);
        }

        if ($this->containsAny($normalizedMessage, ['contact', 'email', 'mail', 'telephone', 'appel', 'tel', 'whatsapp', 'linkedin', 'instagram', 'facebook', 'github', 'reseau', 'reseaux', 'social'])) {
            return $this->buildContactAnswer($profile);
        }

        if ($this->containsAny($normalizedMessage, ['disponible', 'disponibilite', 'mission', 'freelance'])) {
            return $this->buildAvailabilityAnswer($profile);
        }

        if ($this->containsAny($normalizedMessage, ['localisation', 'ou', 'base', 'ville', 'pays', 'cotonou', 'benin'])) {
            return $this->buildLocationAnswer($profile);
        }

        if ($this->containsAny($normalizedMessage, ['cv', 'resume'])) {
            return $this->buildCvAnswer($profile);
        }

        if ($this->containsAny($normalizedMessage, ['video', 'presentation'])) {
            return $this->buildVideoAnswer($profile);
        }

        return $this->buildGenericAnswer($profile, $projects, $skills, $certifications);
    }

    private function buildRemotePrompt(string $message, array $history = []): string
    {
        $profile = $this->tryOrDefault(fn () => (new Profile())->current(), null);
        $projects = $this->tryOrDefault(fn () => array_slice((new Project())->publicAll(), 0, 5), []);
        $skills = $this->tryOrDefault(fn () => array_slice((new Skill())->active(), 0, 10), []);
        $certifications = $this->tryOrDefault(fn () => array_slice((new Certification())->active(), 0, 6), []);
        $knowledgeItems = $this->tryOrDefault(fn () => array_slice((new ChatbotKnowledge())->active(), 0, 8), []);
        $socialLinks = is_array($profile) ? profile_social_links($profile) : [];
        $history = $this->sanitizeHistory($history);

        $lines = [
            'You are the assistant for a developer portfolio website.',
            'Answer in French unless the user clearly uses another language.',
            'Use only the portfolio data below.',
            'If information is missing or uncertain, say so clearly and suggest the contact form or contact page.',
            'Keep answers concise, useful and natural.',
            '',
            'Portfolio context:',
        ];

        if (is_array($profile) && $profile !== []) {
            $lines[] = '- Name: ' . ($profile['full_name'] ?? '');
            $lines[] = '- Title: ' . ($profile['title'] ?? '');
            if (!empty($profile['bio'])) {
                $lines[] = '- Bio: ' . $this->limitText((string) $profile['bio'], 320);
            }
            if (!empty($profile['email'])) {
                $lines[] = '- Email: ' . $profile['email'];
            }
            if (!empty($profile['location'])) {
                $lines[] = '- Location: ' . $profile['location'];
            }
            if (!empty($profile['availability'])) {
                $lines[] = '- Availability: ' . str_replace('_', ' ', (string) $profile['availability']);
            }
            if (!empty($profile['website_url'])) {
                $websiteUrl = absolute_url((string) $profile['website_url']);
                if ($websiteUrl !== null) {
                    $lines[] = '- Website: ' . $websiteUrl;
                }
            }
            if (!empty($profile['cv_url'])) {
                $cvUrl = absolute_url((string) $profile['cv_url']);
                if ($cvUrl !== null) {
                    $lines[] = '- CV: ' . $cvUrl;
                }
            }
            if (!empty($profile['presentation_video_url'])) {
                $videoUrl = absolute_url((string) $profile['presentation_video_url']);
                if ($videoUrl !== null) {
                    $lines[] = '- Presentation video: ' . $videoUrl;
                }
            }
        }

        if ($socialLinks !== []) {
            $lines[] = '';
            $lines[] = 'Social links:';
            foreach ($socialLinks as $link) {
                $lines[] = '- ' . ($link['label'] ?? 'Link') . ': ' . ($link['url'] ?? '');
            }
        }

        if ($projects !== []) {
            $lines[] = '';
            $lines[] = 'Projects:';
            foreach ($projects as $project) {
                $lines[] = '- ' . trim(implode(' | ', array_filter([
                    (string) ($project['titre'] ?? ''),
                    $this->limitText((string) ($project['description'] ?? ''), 180),
                    !empty($project['technologies']) ? 'Tech: ' . $this->limitText((string) $project['technologies'], 120) : '',
                    !empty($project['demo_url']) ? 'Demo: ' . (absolute_url((string) $project['demo_url']) ?? (string) $project['demo_url']) : '',
                ])));
            }
        }

        if ($skills !== []) {
            $lines[] = '';
            $lines[] = 'Skills:';
            foreach ($skills as $skill) {
                $lines[] = '- ' . trim(implode(' | ', array_filter([
                    (string) ($skill['nom'] ?? ''),
                    !empty($skill['categorie']) ? 'Category: ' . $skill['categorie'] : '',
                    isset($skill['niveau']) ? 'Level: ' . $skill['niveau'] : '',
                ])));
            }
        }

        if ($certifications !== []) {
            $lines[] = '';
            $lines[] = 'Certifications:';
            foreach ($certifications as $certification) {
                $lines[] = '- ' . trim(implode(' | ', array_filter([
                    (string) ($certification['titre'] ?? ''),
                    !empty($certification['organisme']) ? 'Issuer: ' . $certification['organisme'] : '',
                    !empty($certification['date_obtention']) ? 'Obtained: ' . $certification['date_obtention'] : '',
                ])));
            }
        }

        if ($knowledgeItems !== []) {
            $lines[] = '';
            $lines[] = 'Knowledge base:';
            foreach ($knowledgeItems as $item) {
                $lines[] = '- Q: ' . $this->limitText((string) ($item['question'] ?? ''), 120)
                    . ' | A: ' . $this->limitText((string) ($item['answer'] ?? ''), 220);
            }
        }

        if ($history !== []) {
            $lines[] = '';
            $lines[] = 'Recent conversation:';
            foreach ($history as $entry) {
                $role = ($entry['role'] ?? 'user') === 'assistant' ? 'Assistant' : 'User';
                $lines[] = '- ' . $role . ': ' . $this->limitText((string) ($entry['content'] ?? ''), 220);
            }
        }

        $lines[] = '';
        $lines[] = 'User question: ' . $message;

        return implode(PHP_EOL, $lines);
    }

    private function normalizeRemoteUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return 'https://api.groq.com/openai/v1/chat/completions';
        }

        if (preg_match('#/(chat/completions|responses)$#', $url) === 1) {
            return $url;
        }

        if (str_ends_with($url, '/openai/v1') || str_ends_with($url, '/v1')) {
            return $url . '/chat/completions';
        }

        return $url;
    }

    private function extractRemoteAnswer(array $response): ?string
    {
        $outputText = $response['output_text'] ?? null;
        if (is_string($outputText) && trim($outputText) !== '') {
            return trim($outputText);
        }

        $texts = [];
        foreach ($response['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message' || !is_array($item['content'] ?? null)) {
                continue;
            }
            foreach ($item['content'] as $content) {
                $text = $content['text'] ?? $content['value'] ?? null;
                if (is_string($text) && trim($text) !== '') {
                    $texts[] = trim($text);
                }
            }
        }

        $answer = trim(implode(PHP_EOL . PHP_EOL, $texts));
        return $answer !== '' ? $answer : null;
    }

    private function extractChatCompletionAnswer(array $response): ?string
    {
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $item) {
                $text = $item['text'] ?? $item['value'] ?? null;
                if (is_string($text) && trim($text) !== '') {
                    $parts[] = trim($text);
                }
            }
            $answer = trim(implode(PHP_EOL . PHP_EOL, $parts));
            return $answer !== '' ? $answer : null;
        }

        return null;
    }

    private function postJson(string $url, array $headers, string $body): array
    {
        $timeout = max(5, (int) env('GROQ_TIMEOUT', env('XAI_TIMEOUT', 20)));
        $caBundle = $this->resolveCaBundlePath();

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $options = [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ];

            if ($caBundle !== null) {
                $options[CURLOPT_CAINFO] = $caBundle;
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException($error !== '' ? $error : 'HTTP request failed.');
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return [$statusCode, (string) $response];
        }

        $httpOptions = [
            'method' => 'POST',
            'header' => implode(chr(13) . chr(10), $headers),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ];

        $sslOptions = [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ];
        if ($caBundle !== null) {
            $sslOptions['cafile'] = $caBundle;
        }

        $context = stream_context_create([
            'http' => $httpOptions,
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
            throw new RuntimeException((string) ($details['message'] ?? 'HTTP request failed.'));
        }

        return [$statusCode, (string) $response];
    }

    private function resolveCaBundlePath(): ?string
    {
        $candidates = array_filter(array_map(
            static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim(str_replace('\\', '/', $value)) : null,
            [
                env('GROQ_CA_BUNDLE', env('XAI_CA_BUNDLE', '')),
                ini_get('curl.cainfo') ?: '',
                ini_get('openssl.cafile') ?: '',
                BASE_PATH . '/storage/certs/cacert.pem',
                'C:/wamp64/apps/phpmyadmin5.2.3/vendor/composer/ca-bundle/res/cacert.pem',
                'C:/wamp64/www/lifac/wp-includes/certificates/ca-bundle.crt',
            ]
        ));

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function findKnowledgeAnswer(string $normalizedMessage): ?string
    {
        if ($normalizedMessage === '') {
            return null;
        }

        $messageTokens = $this->tokens($normalizedMessage);
        $bestScore = 0;
        $bestAnswer = null;

        try {
            foreach ((new ChatbotKnowledge())->active() as $item) {
                $score = 0;
                $question = $this->normalize((string) ($item['question'] ?? ''));
                $answer = trim((string) ($item['answer'] ?? ''));
                if ($answer === '') {
                    continue;
                }

                if ($question !== '' && (str_contains($normalizedMessage, $question) || str_contains($question, $normalizedMessage))) {
                    $score += 6;
                }

                foreach (array_map('trim', explode(',', (string) ($item['keywords'] ?? ''))) as $keyword) {
                    $normalizedKeyword = $this->normalize($keyword);
                    if ($normalizedKeyword === '') {
                        continue;
                    }
                    if (str_contains($normalizedMessage, $normalizedKeyword)) {
                        $score += 4;
                    }
                }

                foreach ($this->tokens($question) as $token) {
                    if ($token !== '' && in_array($token, $messageTokens, true)) {
                        $score++;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestAnswer = $answer;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return $bestScore >= 2 ? $bestAnswer : null;
    }

    private function buildProfileAnswer(?array $profile): string
    {
        if (!is_array($profile) || $profile === []) {
            return 'Le profil détaillé n\'est pas encore disponible. Tu peux utiliser la page Contact pour demander plus d\'informations.';
        }

        $segments = [];
        if (!empty($profile['full_name'])) {
            $segments[] = (string) $profile['full_name'];
        }
        if (!empty($profile['title'])) {
            $segments[] = (string) $profile['title'];
        }
        $answer = implode(', ', $segments);
        if (!empty($profile['bio'])) {
            $bio = $this->limitText((string) $profile['bio'], 220);
            $answer = $answer !== '' ? $answer . '. ' . $bio : $bio;
        }

        return $answer !== ''
            ? $answer
            : 'Je peux surtout te renseigner sur le profil, les projets, les compétences, les certifications et le contact.';
    }

    private function buildProjectsAnswer(array $projects): string
    {
        if ($projects === []) {
            return 'Je n\'ai pas encore de projets publics à proposer dans le portfolio.';
        }

        $items = [];
        foreach (array_slice($projects, 0, 3) as $project) {
            $title = trim((string) ($project['titre'] ?? ''));
            if ($title === '') {
                continue;
            }
            $summary = trim((string) ($project['description'] ?? ''));
            $items[] = $summary !== ''
                ? $title . ' : ' . $this->limitText($summary, 90)
                : $title;
        }

        return $items !== []
            ? 'Voici quelques projets ? retenir : ' . implode(' ; ', $items) . '.'
            : 'Des projets sont bien pr?sents, mais leurs descriptions sont encore trop courtes pour les r?sumer ici.';
    }

    private function buildSkillsAnswer(array $skills): string
    {
        if ($skills === []) {
            return 'Les comp?tences ne sont pas encore renseign?es dans le portfolio.';
        }

        $names = array_values(array_filter(array_map(static fn (array $skill): string => trim((string) ($skill['nom'] ?? '')), $skills)));
        if ($names === []) {
            return 'Les comp?tences sont en cours de pr?paration.';
        }

        return 'Comp?tences mises en avant : ' . implode(', ', array_slice($names, 0, 8)) . '.';
    }

    private function buildCertificationsAnswer(array $certifications): string
    {
        if ($certifications === []) {
            return 'Aucune certification publique nest disponible pour le moment.';
        }

        $titles = array_values(array_filter(array_map(static fn (array $certification): string => trim((string) ($certification['titre'] ?? '')), $certifications)));
        return $titles !== []
            ? 'Certifications disponibles : ' . implode(', ', array_slice($titles, 0, 5)) . '.'
            : 'Des certifications sont r?f?renc?es, mais sans titre exploitable pour le moment.';
    }

    private function buildContactAnswer(?array $profile): string
    {
        if (!is_array($profile) || $profile === []) {
            return 'Le plus simple est de passer par la page Contact du site.';
        }

        $parts = [];
        if (!empty($profile['email'])) {
            $parts[] = 'email : ' . $profile['email'];
        }
        if (!empty($profile['phone'])) {
            $parts[] = 't?l?phone : ' . $profile['phone'];
        }
        if (!empty($profile['website_url'])) {
            $websiteUrl = absolute_url((string) $profile['website_url']);
            if ($websiteUrl !== null) {
                $parts[] = 'site : ' . $websiteUrl;
            }
        }

        $socialLinks = profile_social_links($profile);
        if ($socialLinks !== []) {
            $labels = array_map(static fn (array $link): string => (string) ($link['label'] ?? 'reseau social'), array_slice($socialLinks, 0, 4));
            $parts[] = 'r?seaux : ' . implode(', ', $labels);
        }

        return $parts !== []
            ? 'Tu peux le contacter via ' . implode(' | ', $parts) . '. Tu peux aussi utiliser la page Contact.'
            : 'Tu peux utiliser la page Contact pour ecrire directement.';
    }

    private function buildAvailabilityAnswer(?array $profile): string
    {
        $availability = trim((string) ($profile['availability'] ?? ''));
        if ($availability === '') {
            return 'La disponibilit? n est pas pr?cis?e pour le moment. Le plus simple est de demander via la page Contact.';
        }

        return 'Disponibilit? actuelle : ' . str_replace('_', ' ', $availability) . '.';
    }

    private function buildLocationAnswer(?array $profile): string
    {
        $location = trim((string) ($profile['location'] ?? ''));
        return $location !== ''
            ? 'Base actuelle : ' . $location . '.'
            : 'La localisation nest pas encore renseignee publiquement.';
    }

    private function buildCvAnswer(?array $profile): string
    {
        $cvUrl = absolute_url($profile['cv_url'] ?? null) ?? '';
        return $cvUrl !== ''
            ? 'Le CV est disponible ici : ' . $cvUrl
            : 'Le CV nest pas encore publie. Tu peux passer par la page Contact pour le demander.';
    }

    private function buildVideoAnswer(?array $profile): string
    {
        $videoUrl = absolute_url($profile['presentation_video_url'] ?? null) ?? '';
        return $videoUrl !== ''
            ? 'La vid?o de pr?sentation est disponible depuis la page ? propos et ici : ' . $videoUrl
            : 'La vid?o de pr?sentation n est pas encore renseign?e.';
    }

    private function buildGenericAnswer(?array $profile, array $projects, array $skills, array $certifications): string
    {
        $name = trim((string) ($profile['full_name'] ?? 'ce profil'));
        $topics = [];
        if ($projects !== []) {
            $topics[] = 'les projets';
        }
        if ($skills !== []) {
            $topics[] = 'les comp?tences';
        }
        if ($certifications !== []) {
            $topics[] = 'les certifications';
        }
        $topics[] = 'le contact';

        return 'Je peux t\'aider sur ' . ($name !== '' ? $name : 'ce portfolio') . ' : demande-moi par exemple le profil, ' . implode(', ', $topics) . '.';
    }

    private function sanitizeHistory(array $history): array
    {
        $clean = [];
        foreach (array_slice($history, -8) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $role = (string) ($entry['role'] ?? 'user');
            $role = in_array($role, ['user', 'assistant'], true) ? $role : 'user';
            $content = trim((string) ($entry['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $clean[] = [
                'role' => $role,
                'content' => $this->limitText($content, 260),
            ];
        }

        return $clean;
    }

    private function tokens(string $text): array
    {
        $tokens = preg_split('/[^a-z0-9]+/i', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $tokens), static fn (string $token): bool => strlen($token) >= 2)));
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function mapRemoteStatus(int $statusCode): string
    {
        return match (true) {
            $statusCode === 401 => 'unauthorized',
            $statusCode === 403 => 'forbidden',
            $statusCode === 404 => 'not_found',
            $statusCode === 408 => 'timeout',
            $statusCode === 429 => 'rate_limited',
            $statusCode >= 500 => 'provider_error',
            default => 'http_error',
        };
    }

    private function shouldTryNextRequest(int $statusCode): bool
    {
        return in_array($statusCode, [400, 404, 405, 415, 422, 500, 502, 503, 504], true);
    }

    private function logRemoteFailure(string $url, array $meta, ?string $responseBody): void
    {
        $directory = STORAGE_PATH . '/logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = sprintf(
            "[%s] chatbot remote failure | url=%s | status=%s | code=%s | error=%s | body=%s%s",
            date('c'),
            $url,
            (string) ($meta['status'] ?? 'unknown'),
            isset($meta['status_code']) ? (string) $meta['status_code'] : '-',
            $this->limitText((string) ($meta['error'] ?? ''), 220),
            $this->limitText((string) ($responseBody ?? ''), 260),
            PHP_EOL
        );

        @file_put_contents($directory . '/chatbot.log', $line, FILE_APPEND);
    }

    private function tryOrDefault(callable $callback, mixed $default): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $default;
        }
    }

    private function limitText(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(0, $length - 3))) . '...';
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return '';
        }

        $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) : false;
        $normalized = is_string($ascii) && $ascii !== '' ? strtolower($ascii) : $text;
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;
        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}

