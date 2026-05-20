<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Skill;
use App\Services\ActivityService;

class SkillController extends Controller
{
    private Skill $model;

    public function __construct()
    {
        $this->model = new Skill();
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/skills', ['skills' => $this->model->all('categorie ASC, ordre ASC')], 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->payload($_POST);
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/skills', 422, $errors);
        }

        $this->model->create($data);
        ActivityService::log('skill.create', 'Creation competence ' . $data['nom']);
        flash('success', 'Competence ajoutee.');
        redirect('/admin/skills');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $skill = $this->model->find($id);
        if (!$skill) {
            $this->fail('Competence introuvable.', '/admin/skills', 404);
        }

        $data = array_merge($skill, $this->payload($_POST, false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/skills', 422, $errors);
        }

        $this->model->update($id, $data);
        ActivityService::log('skill.update', 'Mise a jour competence #' . $id);
        flash('success', 'Competence mise a jour.');
        redirect('/admin/skills');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Competence introuvable.', '/admin/skills', 404);
        }

        $this->model->delete($id);
        ActivityService::log('skill.delete', 'Suppression competence #' . $id);
        flash('success', 'Competence supprimee.');
        redirect('/admin/skills');
    }

    public function indexApi(): void
    {
        $skills = auth_check()
            ? $this->model->all('categorie ASC, ordre ASC')
            : $this->model->active();

        $this->json(['success' => true, 'data' => $skills]);
    }

    public function storeApi(): void
    {
        $this->requireAdmin();

        $data = $this->payload($this->input());
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/skills', 422, $errors);
        }

        $this->json(['success' => true, 'id' => $this->model->create($data)], 201);
    }

    public function updateApi(string $id): void
    {
        $this->requireAdmin();

        $skill = $this->model->find($id);
        if (!$skill) {
            $this->fail('Competence introuvable.', '/api/v1/skills', 404);
        }

        $data = array_merge($skill, $this->payload($this->input(), false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/skills', 422, $errors);
        }

        $this->model->update($id, $data);
        $this->json(['success' => true]);
    }

    public function destroyApi(string $id): void
    {
        $this->requireAdmin();

        if (!$this->model->find($id)) {
            $this->fail('Competence introuvable.', '/api/v1/skills', 404);
        }

        $this->model->delete($id);
        $this->json(['success' => true]);
    }

    private function payload(array $data, bool $applyDefaults = true): array
    {
        $payload = [];

        if ($applyDefaults || array_key_exists('nom', $data)) {
            $payload['nom'] = trim((string) ($data['nom'] ?? ''));
        }
        if ($applyDefaults || array_key_exists('categorie', $data)) {
            $categorie = trim((string) ($data['categorie'] ?? 'Autre')) ?: 'Autre';
            $payload['categorie'] = in_array($categorie, skill_category_options(), true) ? $categorie : 'Autre';
        }
        if ($applyDefaults || array_key_exists('niveau', $data)) {
            $niveau = trim((string) ($data['niveau'] ?? 'Intermediaire')) ?: 'Intermediaire';
            $payload['niveau'] = in_array($niveau, skill_level_options(), true) ? $niveau : 'Intermediaire';
        }
        if ($applyDefaults || array_key_exists('icone', $data)) {
            $payload['icone'] = clean_nullable($data['icone'] ?? '');
        }
        if ($applyDefaults || array_key_exists('description', $data)) {
            $payload['description'] = clean_nullable($data['description'] ?? '');
        }
        if ($applyDefaults || array_key_exists('est_active', $data)) {
            $payload['est_active'] = sanitize_bool($data['est_active'] ?? 0);
        }
        if ($applyDefaults || array_key_exists('ordre', $data)) {
            $payload['ordre'] = (int) ($data['ordre'] ?? 0);
        }

        return $payload;
    }

    private function validatePayload(array $data): array
    {
        $errors = validate_required($data, ['nom']);
        if (!in_array($data['categorie'] ?? 'Autre', skill_category_options(), true)) {
            $errors['categorie'] = 'Categorie invalide.';
        }
        if (!in_array($data['niveau'] ?? 'Intermediaire', skill_level_options(), true)) {
            $errors['niveau'] = 'Niveau invalide.';
        }

        return $errors;
    }
}
