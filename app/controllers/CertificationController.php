<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Certification;
use App\Services\ActivityService;

class CertificationController extends Controller
{
    private Certification $model;

    public function __construct()
    {
        $this->model = new Certification();
    }

    public function adminIndex(): void
    {
        $this->requireAdmin();
        $certifications = $this->model->all('ordre ASC, date_obtention DESC');
        $status = trim((string) ($_GET['status'] ?? ''));

        if ($status !== '') {
            $certifications = array_values(array_filter($certifications, fn ($item) => $this->statusOf($item) === $status));
        }

        $this->view('admin/certifications', ['certifications' => $certifications, 'filters' => ['status' => $status]], 'admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('admin/certification-form', ['certification' => null], 'admin');
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $certification = $this->model->find($id);
        if (!$certification) {
            $this->fail('Certification introuvable.', '/admin/certifications', 404);
        }

        $this->view('admin/certification-form', compact('certification'), 'admin');
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $data = $this->payload($_POST);
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/certifications/create', 422, $errors);
        }

        $this->model->create($data);
        ActivityService::log('certification.create', 'Creation certification ' . $data['titre']);
        flash('success', 'Certification creee.');
        redirect('/admin/certifications');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $certification = $this->model->find($id);
        if (!$certification) {
            $this->fail('Certification introuvable.', '/admin/certifications', 404);
        }

        $data = array_merge($certification, $this->payload($_POST, false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/certifications/' . $id . '/edit', 422, $errors);
        }

        $this->model->update($id, $data);
        ActivityService::log('certification.update', 'Mise a jour certification #' . $id);
        flash('success', 'Certification mise a jour.');
        redirect('/admin/certifications');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (!$this->model->find($id)) {
            $this->fail('Certification introuvable.', '/admin/certifications', 404);
        }

        $this->model->delete($id);
        flash('success', 'Certification supprimee.');
        redirect('/admin/certifications');
    }

    public function indexApi(): void
    {
        $items = auth_check()
            ? $this->model->all('ordre ASC, date_obtention DESC')
            : $this->model->active();

        $this->json(['success' => true, 'data' => $items]);
    }

    public function storeApi(): void
    {
        $this->requireAdmin();

        $data = $this->payload($this->input());
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/certifications', 422, $errors);
        }

        $this->json(['success' => true, 'id' => $this->model->create($data)], 201);
    }

    public function updateApi(string $id): void
    {
        $this->requireAdmin();

        $certification = $this->model->find($id);
        if (!$certification) {
            $this->fail('Certification introuvable.', '/api/v1/certifications', 404);
        }

        $data = array_merge($certification, $this->payload($this->input(), false));
        $errors = $this->validatePayload($data);
        if ($errors !== []) {
            $this->fail(reset($errors), '/api/v1/certifications', 422, $errors);
        }

        $this->model->update($id, $data);
        $this->json(['success' => true]);
    }

    public function destroyApi(string $id): void
    {
        $this->requireAdmin();

        if (!$this->model->find($id)) {
            $this->fail('Certification introuvable.', '/api/v1/certifications', 404);
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
        if ($applyDefaults || array_key_exists('organisme', $data)) {
            $payload['organisme'] = trim((string) ($data['organisme'] ?? ''));
        }
        if ($applyDefaults || array_key_exists('date_obtention', $data)) {
            $payload['date_obtention'] = trim((string) ($data['date_obtention'] ?? date('Y-m-d')));
        }
        if ($applyDefaults || array_key_exists('date_expiration', $data)) {
            $payload['date_expiration'] = clean_nullable($data['date_expiration'] ?? '');
        }
        if ($applyDefaults || array_key_exists('credential_id', $data)) {
            $payload['credential_id'] = clean_nullable($data['credential_id'] ?? '');
        }
        if ($applyDefaults || array_key_exists('badge_url', $data)) {
            $payload['badge_url'] = clean_nullable($data['badge_url'] ?? '');
        }
        if ($applyDefaults || array_key_exists('lien_verification', $data)) {
            $payload['lien_verification'] = clean_nullable($data['lien_verification'] ?? '');
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
        $errors = validate_required($data, ['titre', 'organisme', 'date_obtention']);

        if (!is_valid_date_or_empty($data['date_obtention'] ?? null)) {
            $errors['date_obtention'] = 'Date d obtention invalide.';
        }
        if (!is_valid_date_or_empty($data['date_expiration'] ?? null)) {
            $errors['date_expiration'] = 'Date d expiration invalide.';
        }

        if (!empty($data['date_obtention']) && !empty($data['date_expiration']) && $data['date_expiration'] < $data['date_obtention']) {
            $errors['date_expiration'] = 'La date d expiration doit etre posterieure a la date d obtention.';
        }

        foreach (['badge_url', 'lien_verification'] as $field) {
            if (!is_valid_url_or_empty($data[$field] ?? null)) {
                $errors[$field] = 'Merci de fournir une URL valide.';
            }
        }

        return $errors;
    }

    private function statusOf(array $certification): string
    {
        if (empty($certification['est_active'])) {
            return 'hidden';
        }
        if (!empty($certification['date_expiration']) && $certification['date_expiration'] < date('Y-m-d')) {
            return 'expired';
        }
        if (!empty($certification['date_expiration']) && $certification['date_expiration'] <= date('Y-m-d', strtotime('+30 days'))) {
            return 'expiring';
        }
        return 'active';
    }
}
