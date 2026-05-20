<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Services\ActivityService;
use App\Services\NotificationService;
use App\Services\ProjectService;

class PostController extends Controller
{
    private Post $model;

    public function __construct()
    {
        $this->model = new Post();
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/blog', ['posts' => $this->model->all('created_at DESC')], 'admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('admin/blog-form', ['post' => null], 'admin');
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $post = $this->model->find($id);
        if (!$post) {
            $this->fail('Article introuvable.', '/admin/blog', 404);
        }

        $this->view('admin/blog-form', compact('post'), 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->payload($_POST);
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/blog/create', 422, $errors);
        }

        if (!empty($_FILES['image']['name'])) {
            $data['image_url'] = upload_file($_FILES['image'], 'uploads/blog', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        }

        $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null);
        $id = $this->model->create($data);

        $this->recordPostEvents($data, (int) $id, 'post.create', 'Creation de l article ' . $data['titre']);
        flash('success', 'Article cree.');
        redirect('/admin/blog');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $post = $this->model->find($id);
        if (!$post) {
            $this->fail('Article introuvable.', '/admin/blog', 404);
        }

        $data = array_merge($post, $this->payload($_POST, false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/blog/' . $id . '/edit', 422, $errors);
        }

        if (!empty($_FILES['image']['name'])) {
            $data['image_url'] = upload_file($_FILES['image'], 'uploads/blog', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        }

        $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null, (int) $id);
        $this->model->update($id, $data);

        $this->recordPostEvents($data, (int) $id, 'post.update', 'Mise a jour de l article #' . $id);
        flash('success', 'Article mis a jour.');
        redirect('/admin/blog');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Article introuvable.', '/admin/blog', 404);
        }

        $this->model->delete($id);
        ActivityService::log('post.delete', 'Suppression de l article #' . $id);
        flash('success', 'Article supprime.');
        redirect('/admin/blog');
    }

    public function indexApi(): void
    {
        $posts = auth_check() ? $this->model->all('created_at DESC') : $this->model->published();
        $this->json(['success' => true, 'data' => $posts]);
    }

    public function storeApi(): void
    {
        $this->requireAdmin();

        $data = $this->payload($this->input());
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/posts', 422, $errors);
        }

        $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null);
        $id = $this->model->create($data);
        $this->recordPostEvents($data, (int) $id, 'post.create', 'Creation de l article ' . $data['titre']);
        $this->json(['success' => true, 'id' => $id], 201);
    }

    public function updateApi(string $id): void
    {
        $this->requireAdmin();

        $post = $this->model->find($id);
        if (!$post) {
            $this->fail('Article introuvable.', '/api/v1/posts', 404);
        }

        $data = array_merge($post, $this->payload($this->input(), false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/posts', 422, $errors);
        }

        $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null, (int) $id);
        $this->model->update($id, $data);
        $this->recordPostEvents($data, (int) $id, 'post.update', 'Mise a jour de l article #' . $id);
        $this->json(['success' => true]);
    }

    public function destroyApi(string $id): void
    {
        $this->requireAdmin();

        if (!$this->model->find($id)) {
            $this->fail('Article introuvable.', '/api/v1/posts', 404);
        }

        $this->model->delete($id);
        $this->json(['success' => true]);
    }

    private function payload(array $data, bool $applyDefaults = true): array
    {
        $payload = [];

        if ($applyDefaults || array_key_exists('titre', $data)) {
            $payload['titre'] = trim((string) ($data['titre'] ?? ''));
        }
        if ($applyDefaults || array_key_exists('category', $data)) {
            $payload['category'] = clean_nullable($data['category'] ?? '') ?? 'autre';
        }
        if ($applyDefaults || array_key_exists('slug', $data)) {
            $payload['slug'] = clean_nullable($data['slug'] ?? '');
        }
        if ($applyDefaults || array_key_exists('extrait', $data)) {
            $payload['extrait'] = clean_nullable($data['extrait'] ?? '');
        }
        if ($applyDefaults || array_key_exists('contenu', $data)) {
            $payload['contenu'] = $this->sanitizeContent($data['contenu'] ?? '');
        }
        if ($applyDefaults || array_key_exists('tags', $data)) {
            $payload['tags'] = clean_nullable($data['tags'] ?? '');
        }
        if ($applyDefaults || array_key_exists('image_url', $data)) {
            $payload['image_url'] = clean_nullable($data['image_url'] ?? '');
        }
        if ($applyDefaults || array_key_exists('statut', $data)) {
            $statut = (string) ($data['statut'] ?? 'brouillon');
            $payload['statut'] = in_array($statut, ['brouillon', 'publie'], true) ? $statut : 'brouillon';
        }
        if ($applyDefaults || array_key_exists('published_at', $data)) {
            $payload['published_at'] = normalize_datetime_input($data['published_at'] ?? null);
        }
        if ($applyDefaults) {
            $payload['view_count'] = 0;
        }

        if (($payload['statut'] ?? ($data['statut'] ?? null)) === 'publie' && empty($payload['published_at'])) {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    private function validatePayload(array $data): array
    {
        $errors = validate_required($data, ['titre']);
        if (!in_array($data['statut'] ?? 'brouillon', ['brouillon', 'publie'], true)) {
            $errors['statut'] = 'Statut invalide.';
        }
        if (!is_valid_url_or_empty($data['image_url'] ?? null)) {
            $errors['image_url'] = 'Merci de fournir une URL valide.';
        }
        return $errors;
    }

    private function sanitizeContent(mixed $value): ?string
    {
        return sanitize_rich_text($value);
    }

    private function recordPostEvents(array $data, int $postId, string $action, string $message): void
    {
        ActivityService::log($action, $message);
        if (($data['statut'] ?? 'brouillon') === 'publie') {
            ActivityService::log('post.publish', 'Article publie : ' . ($data['titre'] ?? ('#' . $postId)));
            NotificationService::push('post', 'Article publie', 'L article ' . ($data['titre'] ?? ('#' . $postId)) . ' est en ligne.', '/admin/blog', 'post-published:' . $postId);
        }
    }
}
