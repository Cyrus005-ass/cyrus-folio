<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Collaboration;
use App\Models\Project;

class CollaborationController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAdmin();
        $soloProjects = Database::query('SELECT p.* FROM projects p LEFT JOIN collaborations c ON c.project_id = p.id WHERE c.id IS NULL ORDER BY p.titre ASC')->fetchAll();
        $collaborativeProjects = Database::query('SELECT p.id, p.titre, COUNT(c.id) collaborators_count FROM projects p INNER JOIN collaborations c ON c.project_id = p.id GROUP BY p.id, p.titre ORDER BY p.titre ASC')->fetchAll();

        $this->view('admin/collaborations', [
            'collaborations' => (new Collaboration())->all('created_at DESC'),
            'projects' => (new Project())->all('titre ASC'),
            'soloProjects' => $soloProjects,
            'collaborativeProjects' => $collaborativeProjects,
        ], 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->payload($_POST);
        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/collaborations', 422, $errors);
        }

        (new Collaboration())->create($data);
        flash('success', 'Collaboration ajoutee.');
        redirect('/admin/collaborations');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new Collaboration();
        $item = $model->find($id);
        if (!$item) {
            $this->fail('Collaboration introuvable.', '/admin/collaborations', 404);
        }

        $data = array_merge($item, $this->payload($_POST));
        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/collaborations', 422, $errors);
        }

        $model->update($id, $data);
        flash('success', 'Collaboration mise a jour.');
        redirect('/admin/collaborations');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $model = new Collaboration();
        if (!$model->find($id)) {
            $this->fail('Collaboration introuvable.', '/admin/collaborations', 404);
        }

        $model->delete($id);
        flash('success', 'Collaboration supprimee.');
        redirect('/admin/collaborations');
    }

    private function payload(array $input): array
    {
        return [
            'project_id' => is_numeric($input['project_id'] ?? null) ? (int) $input['project_id'] : null,
            'nom_membre' => trim((string) ($input['nom_membre'] ?? '')),
            'role' => trim((string) ($input['role'] ?? '')),
            'email' => clean_nullable($input['email'] ?? ''),
            'linkedin_url' => clean_nullable($input['linkedin_url'] ?? ''),
            'portfolio_url' => clean_nullable($input['portfolio_url'] ?? ''),
            'github_url' => clean_nullable($input['github_url'] ?? ''),
            'contribution' => clean_nullable($input['contribution'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = validate_required($data, ['nom_membre', 'role']);
        if (!is_valid_email($data['email'] ?? null) && $data['email'] !== null) {
            $errors['email'] = 'Merci de fournir une adresse email valide.';
        }
        foreach (['linkedin_url', 'portfolio_url', 'github_url'] as $field) {
            if (!is_valid_url_or_empty($data[$field] ?? null)) {
                $errors[$field] = 'Merci de fournir une URL valide.';
            }
        }

        return $errors;
    }
}
