<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Theme;
use App\Services\ThemeService;

class ThemeController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAdmin();
        $this->view('admin/theme', ['theme' => ThemeService::activeTheme()], 'admin');
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $defaults = theme_defaults();
        $data = [
            'nom' => trim((string) ($_POST['nom'] ?? $defaults['nom'])) ?: $defaults['nom'],
            'primary_color' => (string) ($_POST['primary_color'] ?? $defaults['primary_color']),
            'secondary_color' => (string) ($_POST['secondary_color'] ?? $defaults['secondary_color']),
            'accent_color' => (string) ($_POST['accent_color'] ?? $defaults['accent_color']),
            'background_color' => (string) ($_POST['background_color'] ?? $defaults['background_color']),
            'text_color' => (string) ($_POST['text_color'] ?? $defaults['text_color']),
            'display_font_family' => trim((string) ($_POST['display_font_family'] ?? $defaults['display_font_family'])) ?: $defaults['display_font_family'],
            'body_font_family' => trim((string) ($_POST['body_font_family'] ?? $defaults['body_font_family'])) ?: $defaults['body_font_family'],
            'font_family' => trim((string) ($_POST['body_font_family'] ?? $defaults['body_font_family'])) ?: $defaults['body_font_family'],
            'animations_enabled' => sanitize_bool($_POST['animations_enabled'] ?? 0),
            'is_active' => 1,
        ];

        $errors = [];
        foreach (['primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color'] as $field) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $data[$field]) !== 1) {
                $errors[$field] = 'Couleur invalide.';
            }
        }

        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/theme', 422, $errors);
        }

        $model = new Theme();
        $active = $model->active();
        if ($active) {
            Database::query('UPDATE themes SET is_active = 0 WHERE id != ?', [$active['id']]);
            $model->update($active['id'], $data);
        } else {
            Database::query('UPDATE themes SET is_active = 0');
            $model->create($data);
        }

        flash('success', 'Theme sauvegarde.');
        redirect('/admin/theme');
    }

    public function reset(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $defaults = theme_defaults();
        $model = new Theme();
        $active = $model->active();
        if ($active) {
            $model->update($active['id'], $defaults);
        } else {
            Database::query('UPDATE themes SET is_active = 0');
            $model->create($defaults);
        }

        flash('success', 'Theme reinitialise.');
        redirect('/admin/theme');
    }
}
