<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Collaboration;
use App\Models\Project;
use App\Services\ActivityService;
use App\Services\NotificationService;
use App\Services\ProjectService;
use Throwable;

class ProjectController extends Controller
{
    private Project $model;

    public function __construct()
    {
        $this->model = new Project();
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/projects', ['projects' => $this->model->all('ordre ASC, created_at DESC')], 'admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('admin/project-form', ['project' => null, 'projectCollaborations' => []], 'admin');
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $project = $this->model->find($id);
        if (!$project) {
            $this->fail('Projet introuvable.', '/admin/projects', 404);
        }

        $projectCollaborations = $this->projectCollaborations((int) $project['id']);

        $this->view('admin/project-form', compact('project', 'projectCollaborations'), 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->payload($_POST);
        $collaboration = $this->collaborationPayload($_POST);
        $errors = array_merge($this->validatePayload($data), $this->validateCollaborationPayload($collaboration));
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/projects/create', 422, $errors);
        }

        $pdo = null;
        try {
            if (!empty($_FILES['image']['name'])) {
                $data['image_url'] = upload_file($_FILES['image'], 'uploads/projects', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            }

            $data['gallery_images'] = $this->handleGalleryFiles([], $_FILES['gallery'] ?? null, []);
            if (empty($data['image_url']) && !empty(decode_json_array($data['gallery_images'] ?? null))) {
                $data['image_url'] = decode_json_array($data['gallery_images'])[0];
            }

            $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null);
            $pdo = Database::connect();
            $pdo->beginTransaction();
            $id = $this->model->create($data);
            $collaborationAdded = $this->persistOptionalCollaboration((int) $id, $collaboration);
            $pdo->commit();

            NotificationService::push('project', 'Nouveau projet', 'Un projet a ete cree.', '/admin/projects');
            $this->recordProjectEvents($data, 'project.create', 'Creation du projet ' . $data['titre'], (int) $id);
            flash('success', $collaborationAdded ? 'Projet cree et collaborateur ajoute.' : 'Projet cree.');
            redirect('/admin/projects');
        } catch (Throwable $e) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->fail('Impossible d\'enregistrer le projet.', '/admin/projects/create');
        }
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $project = $this->model->find($id);
        if (!$project) {
            $this->fail('Projet introuvable.', '/admin/projects', 404);
        }

        $data = array_merge($project, $this->payload($_POST, false));
        $collaboration = $this->collaborationPayload($_POST);
        $errors = array_merge($this->validatePayload($data), $this->validateCollaborationPayload($collaboration));
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/projects/' . $id . '/edit', 422, $errors);
        }

        $pdo = null;
        try {
            if (!empty($_FILES['image']['name'])) {
                $data['image_url'] = upload_file($_FILES['image'], 'uploads/projects', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            }

            $existingGallery = decode_json_array($project['gallery_images'] ?? null);
            $removedGallery = array_values(array_filter(array_map('strval', $_POST['remove_gallery_images'] ?? [])));
            $data['gallery_images'] = $this->handleGalleryFiles($existingGallery, $_FILES['gallery'] ?? null, $removedGallery);
            if (empty($data['image_url']) && !empty(decode_json_array($data['gallery_images'] ?? null))) {
                $data['image_url'] = decode_json_array($data['gallery_images'])[0];
            }

            $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null, (int) $id);
            $pdo = Database::connect();
            $pdo->beginTransaction();
            $this->model->update($id, $data);
            $collaborationAdded = $this->persistOptionalCollaboration((int) $id, $collaboration);
            $pdo->commit();

            $this->recordProjectEvents($data, 'project.update', 'Mise a jour du projet #' . $id, (int) $id);
            flash('success', $collaborationAdded ? 'Projet mis a jour et collaborateur ajoute.' : 'Projet mis a jour.');
            redirect('/admin/projects');
        } catch (Throwable $e) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->fail('Impossible de mettre a jour le projet.', '/admin/projects/' . $id . '/edit');
        }
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Projet introuvable.', '/admin/projects', 404);
        }

        $this->model->delete($id);
        ActivityService::log('project.delete', 'Suppression du projet #' . $id);
        flash('success', 'Projet supprime.');
        redirect('/admin/projects');
    }

    public function indexApi(): void
    {
        $projects = auth_check()
            ? $this->model->all('ordre ASC, created_at DESC')
            : $this->model->publicAll();

        $this->json(['success' => true, 'data' => $projects]);
    }

    public function storeApi(): void
    {
        $this->requireAdmin();

        $input = $this->input();
        $data = $this->payload($input);
        $collaboration = $this->collaborationPayload($input);
        $errors = array_merge($this->validatePayload($data), $this->validateCollaborationPayload($collaboration));
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/projects', 422, $errors);
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null);
            $id = $this->model->create($data);
            $collaborationAdded = $this->persistOptionalCollaboration((int) $id, $collaboration);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->fail('Impossible d\'enregistrer le projet.', '/api/v1/projects', 500);
        }

        NotificationService::push('project', 'Nouveau projet', 'Un projet a ete cree.', '/admin/projects');
        $this->recordProjectEvents($data, 'project.create', 'Creation du projet ' . $data['titre'], (int) $id);
        $this->json(['success' => true, 'id' => $id, 'collaboration_added' => $collaborationAdded], 201);
    }

    public function updateApi(string $id): void
    {
        $this->requireAdmin();

        $project = $this->model->find($id);
        if (!$project) {
            $this->fail('Projet introuvable.', '/api/v1/projects', 404);
        }

        $input = $this->input();
        $data = array_merge($project, $this->payload($input, false));
        $collaboration = $this->collaborationPayload($input);
        $errors = array_merge($this->validatePayload($data), $this->validateCollaborationPayload($collaboration));
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/projects', 422, $errors);
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $data['slug'] = ProjectService::uniqueSlug($this->model, $data['titre'], $data['slug'] ?? null, (int) $id);
            $this->model->update($id, $data);
            $collaborationAdded = $this->persistOptionalCollaboration((int) $id, $collaboration);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->fail('Impossible de mettre a jour le projet.', '/api/v1/projects', 500);
        }

        $this->recordProjectEvents($data, 'project.update', 'Mise a jour du projet #' . $id, (int) $id);
        $this->json(['success' => true, 'collaboration_added' => $collaborationAdded]);
    }

    public function destroyApi(string $id): void
    {
        $this->requireAdmin();

        if (!$this->model->find($id)) {
            $this->fail('Projet introuvable.', '/api/v1/projects', 404);
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
        if ($applyDefaults || array_key_exists('slug', $data)) {
            $payload['slug'] = clean_nullable($data['slug'] ?? '');
        }
        if ($applyDefaults || array_key_exists('description', $data)) {
            $payload['description'] = clean_nullable($data['description'] ?? '');
        }
        if ($applyDefaults || array_key_exists('contenu', $data)) {
            $payload['contenu'] = clean_nullable($data['contenu'] ?? '');
        }
        if ($applyDefaults || array_key_exists('technologies', $data)) {
            $payload['technologies'] = clean_nullable($data['technologies'] ?? '');
        }
        if ($applyDefaults || array_key_exists('image_url', $data)) {
            $payload['image_url'] = clean_nullable($data['image_url'] ?? '');
        }
        if ($applyDefaults || array_key_exists('gallery_images', $data)) {
            $payload['gallery_images'] = encode_json_array(decode_json_array($data['gallery_images'] ?? []));
        }
        if ($applyDefaults || array_key_exists('github_url', $data)) {
            $payload['github_url'] = clean_nullable($data['github_url'] ?? '');
        }
        if ($applyDefaults || array_key_exists('demo_url', $data)) {
            $payload['demo_url'] = clean_nullable($data['demo_url'] ?? '');
        }
        if ($applyDefaults || array_key_exists('statut', $data)) {
            $statut = (string) ($data['statut'] ?? 'brouillon');
            $payload['statut'] = in_array($statut, ['brouillon', 'publie'], true) ? $statut : 'brouillon';
        }
        if ($applyDefaults || array_key_exists('est_mis_en_avant', $data)) {
            $payload['est_mis_en_avant'] = sanitize_bool($data['est_mis_en_avant'] ?? 0);
        }
        if ($applyDefaults || array_key_exists('ordre', $data)) {
            $payload['ordre'] = (int) ($data['ordre'] ?? 0);
        }

        return $payload;
    }

    private function handleGalleryFiles(array $existing, ?array $files, array $removed): ?string
    {
        $gallery = array_values(array_filter($existing, fn ($item) => is_string($item) && $item !== '' && !in_array($item, $removed, true)));
        if ($files === null || empty($files['name']) || !is_array($files['name'])) {
            return encode_json_array($gallery);
        }

        foreach ($files['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $file = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
            $uploaded = upload_file($file, 'uploads/projects', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            if ($uploaded !== null) {
                $gallery[] = $uploaded;
            }
        }

        return encode_json_array($gallery);
    }

    private function collaborationPayload(array $data): array
    {
        return [
            'enabled' => sanitize_bool($data['has_collaboration'] ?? 0),
            'nom_membre' => trim((string) ($data['collaboration_nom_membre'] ?? '')),
            'role' => trim((string) ($data['collaboration_role'] ?? '')),
            'email' => clean_nullable($data['collaboration_email'] ?? ''),
            'linkedin_url' => clean_nullable($data['collaboration_linkedin_url'] ?? ''),
            'portfolio_url' => clean_nullable($data['collaboration_portfolio_url'] ?? ''),
            'github_url' => clean_nullable($data['collaboration_github_url'] ?? ''),
            'contribution' => clean_nullable($data['collaboration_contribution'] ?? ''),
        ];
    }

    private function hasCollaborationInput(array $data): bool
    {
        return !empty($data['enabled'])
            || ($data['nom_membre'] ?? '') !== ''
            || ($data['role'] ?? '') !== ''
            || ($data['email'] ?? null) !== null
            || ($data['linkedin_url'] ?? null) !== null
            || ($data['portfolio_url'] ?? null) !== null
            || ($data['github_url'] ?? null) !== null
            || ($data['contribution'] ?? null) !== null;
    }

    private function validateCollaborationPayload(array $data): array
    {
        if (!$this->hasCollaborationInput($data)) {
            return [];
        }

        $errors = [];

        if (($data['nom_membre'] ?? '') === '') {
            $errors['collaboration_nom_membre'] = 'Le nom du collaborateur est obligatoire.';
        }
        if (($data['role'] ?? '') === '') {
            $errors['collaboration_role'] = 'Le role du collaborateur est obligatoire.';
        }
        if (($data['email'] ?? null) !== null && !is_valid_email($data['email'])) {
            $errors['collaboration_email'] = 'Merci de fournir une adresse email valide.';
        }

        foreach ([
            'linkedin_url' => 'collaboration_linkedin_url',
            'portfolio_url' => 'collaboration_portfolio_url',
            'github_url' => 'collaboration_github_url',
        ] as $field => $errorKey) {
            if (!is_valid_url_or_empty($data[$field] ?? null)) {
                $errors[$errorKey] = 'Merci de fournir une URL valide.';
            }
        }

        return $errors;
    }

    private function persistOptionalCollaboration(int $projectId, array $data): bool
    {
        if (!$this->hasCollaborationInput($data)) {
            return false;
        }

        unset($data['enabled']);
        $data['project_id'] = $projectId;
        (new Collaboration())->create($data);

        return true;
    }

    private function projectCollaborations(int $projectId): array
    {
        return (new Collaboration())->all('created_at ASC', 'project_id = ?', [$projectId]);
    }

    private function recordProjectEvents(array $data, string $action, string $message, int $projectId): void
    {
        ActivityService::log($action, $message);
        if (($data['statut'] ?? 'brouillon') === 'publie') {
            ActivityService::log('project.publish', 'Projet publie : ' . ($data['titre'] ?? ('#' . $projectId)));
            NotificationService::push('project', 'Projet publie', 'Le projet ' . ($data['titre'] ?? ('#' . $projectId)) . ' est visible publiquement.', '/admin/projects', 'project-published:' . $projectId);
        }
    }

    private function validatePayload(array $data): array
    {
        $errors = validate_required($data, ['titre']);

        foreach (['image_url', 'github_url', 'demo_url'] as $field) {
            if (!is_valid_url_or_empty($data[$field] ?? null)) {
                $errors[$field] = 'Merci de fournir une URL valide.';
            }
        }

        if (!in_array($data['statut'] ?? 'brouillon', ['brouillon', 'publie'], true)) {
            $errors['statut'] = 'Statut invalide.';
        }

        return $errors;
    }
}
