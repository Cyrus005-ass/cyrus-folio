<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Profile;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Throwable;

class ProfileController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAdmin();

        $userId = (int) (auth_user()['id'] ?? 0);
        $user = (new User())->find($userId) ?: (auth_user() ?? []);
        $this->view('admin/profile', [
            'profile' => (new Profile())->current(),
            'user' => $user,
            'twoFactor' => $this->twoFactorData($user),
        ], 'admin');
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $userId = (int) (auth_user()['id'] ?? 0);
        if ($userId <= 0) {
            $this->fail('Session invalide.', '/admin/login', 401);
        }

        $profileModel = new Profile();
        $userModel = new User();
        $profile = $profileModel->forUser($userId);

        $data = [
            'user_id' => $userId,
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'bio' => clean_nullable($_POST['bio'] ?? ''),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => clean_nullable($_POST['phone'] ?? ''),
            'location' => clean_nullable($_POST['location'] ?? ''),
            'availability' => in_array((string) ($_POST['availability'] ?? 'disponible'), availability_options(), true) ? (string) ($_POST['availability'] ?? 'disponible') : 'disponible',
            'avatar_url' => clean_nullable($_POST['avatar_url'] ?? ''),
            'cv_url' => clean_nullable($_POST['cv_url'] ?? ''),
            'presentation_video_url' => clean_nullable($_POST['presentation_video_url'] ?? ''),
            'github_url' => clean_nullable($_POST['github_url'] ?? ''),
            'linkedin_url' => clean_nullable($_POST['linkedin_url'] ?? ''),
            'twitter_url' => clean_nullable($_POST['twitter_url'] ?? ''),
            'instagram_url' => clean_nullable($_POST['instagram_url'] ?? ''),
            'whatsapp_url' => clean_nullable($_POST['whatsapp_url'] ?? ''),
            'facebook_url' => clean_nullable($_POST['facebook_url'] ?? ''),
            'website_url' => clean_nullable($_POST['website_url'] ?? ''),
            'other_links' => clean_nullable($_POST['other_links'] ?? ''),
        ];

        $errors = validate_required($data, ['full_name', 'email']);
        if (!is_valid_email($data['email'])) {
            $errors['email'] = 'Merci de fournir une adresse email valide.';
        }
        foreach (['avatar_url', 'cv_url', 'presentation_video_url'] as $field) {
            if (!is_valid_public_asset_url_or_empty($data[$field] ?? null)) {
                $errors[$field] = 'Merci de fournir une URL ou un chemin de fichier valide.';
            }
        }
        foreach (['github_url', 'linkedin_url', 'twitter_url', 'instagram_url', 'whatsapp_url', 'facebook_url', 'website_url'] as $field) {
            if (!is_valid_url_or_empty($data[$field] ?? null)) {
                $errors[$field] = 'Merci de fournir une URL valide.';
            }
        }
        if (!in_array($data['availability'] ?? 'disponible', availability_options(), true)) {
            $errors['availability'] = 'Disponibilit? invalide.';
        }
        foreach (parse_named_links($data['other_links']) as $link) {
            if (!is_valid_url_or_empty($link['url'] ?? null)) {
                $errors['other_links'] = 'Merci de fournir des liens valides.';
                break;
            }
        }

        $existingUser = $userModel->findByEmail($data['email']);
        if ($existingUser && (int) $existingUser['id'] !== $userId) {
            $errors['email'] = 'Cette adresse email est d?j? utilis?e.';
        }

        if ($errors !== []) {
            $this->fail(reset($errors), '/admin/profile', 422, $errors);
        }

        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            if (!empty($_FILES['avatar']['name'])) {
                $data['avatar_url'] = upload_file($_FILES['avatar'], 'uploads/profile', ['jpg', 'jpeg', 'png', 'webp']);
            }
            if (!empty($_FILES['cv']['name'])) {
                $data['cv_url'] = upload_file($_FILES['cv'], 'uploads/profile', ['pdf']);
            }

            if ($profile) {
                $profileModel->update($profile['id'], $data);
            } else {
                $profileModel->create($data);
            }

            $userModel->update($userId, [
                'name' => $data['full_name'],
                'email' => $data['email'],
            ]);

            $pdo->commit();

            $_SESSION['user'] = array_merge(auth_user() ?? [], [
                'id' => $userId,
                'name' => $data['full_name'],
                'email' => $data['email'],
            ]);

            flash('success', 'Profil sauvegard?.');
            redirect('/admin/profile');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->fail('Impossible de sauvegarder le profil.', '/admin/profile');
        }
    }

    public function password(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $user = (new User())->find((int) (auth_user()['id'] ?? 0));
        $oldPassword = (string) ($_POST['old_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        if (!$user || !password_verify($oldPassword, (string) ($user['password'] ?? ''))) {
            $this->fail('Ancien mot de passe incorrect.', '/admin/profile', 422);
        }
        if ($password !== $confirmation || strlen($password) < 8) {
            $this->fail('Mot de passe invalide ou confirmation diff?rente.', '/admin/profile', 422);
        }

        (new User())->update((int) (auth_user()['id'] ?? 0), ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        flash('success', 'Mot de passe mis ? jour.');
        redirect('/admin/profile');
    }

    public function enableTwoFactor(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $userId = (int) (auth_user()['id'] ?? 0);
        $user = (new User())->find($userId);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $secret = (string) ($_POST['two_factor_secret'] ?? '');
        $code = (string) ($_POST['code'] ?? $_POST['two_factor_code'] ?? '');

        if (!$user || !password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            $this->fail('Mot de passe actuel incorrect.', '/admin/profile', 422);
        }
        if (TwoFactorService::enabledForUser($user)) {
            $this->fail('La 2FA est d?j? activ?e sur ce compte.', '/admin/profile', 422);
        }
        if (!TwoFactorService::isValidSecret($secret)) {
            $this->fail('Le secret 2FA g?n?r? est invalide. Recharge la page et r?essaie.', '/admin/profile', 422);
        }
        if (!TwoFactorService::verifyCode($secret, $code)) {
            $this->fail('Le code 2FA saisi est invalide.', '/admin/profile', 422);
        }

        $confirmedAt = date('Y-m-d H:i:s');
        (new User())->update($userId, [
            'two_factor_secret' => strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? ''),
            'two_factor_enabled' => 1,
            'two_factor_confirmed_at' => $confirmedAt,
        ]);

        AuthService::revokeRememberTokens($userId);
        $_SESSION['user'] = array_merge(auth_user() ?? [], [
            'id' => $userId,
            'two_factor_enabled' => 1,
            'two_factor_confirmed_at' => $confirmedAt,
        ]);

        ActivityService::log('two_factor_enabled', 'Activation de la 2FA sur le compte administrateur.', $userId);
        flash('success', '2FA activ?e. Le remember me est d?sactiv? pour ?viter tout contournement.');
        redirect('/admin/profile');
    }

    public function disableTwoFactor(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $userId = (int) (auth_user()['id'] ?? 0);
        $user = (new User())->find($userId);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $code = (string) ($_POST['code'] ?? $_POST['two_factor_code'] ?? '');

        if (!$user || !password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            $this->fail('Mot de passe actuel incorrect.', '/admin/profile', 422);
        }
        if (!TwoFactorService::enabledForUser($user)) {
            $this->fail('La 2FA n\'est pas activ?e sur ce compte.', '/admin/profile', 422);
        }
        if (!TwoFactorService::verifyCode((string) ($user['two_factor_secret'] ?? ''), $code)) {
            $this->fail('Le code 2FA saisi est invalide.', '/admin/profile', 422);
        }

        (new User())->update($userId, [
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
            'two_factor_confirmed_at' => null,
        ]);

        $_SESSION['user'] = array_merge(auth_user() ?? [], [
            'id' => $userId,
            'two_factor_enabled' => 0,
            'two_factor_confirmed_at' => null,
        ]);

        ActivityService::log('two_factor_disabled', 'D?sactivation de la 2FA sur le compte administrateur.', $userId);
        flash('success', '2FA d?sactiv?e sur ce compte.');
        redirect('/admin/profile');
    }

    private function twoFactorData(array $user): array
    {
        $account = trim((string) ($user['email'] ?? ($user['name'] ?? 'admin')));
        $enabled = TwoFactorService::enabledForUser($user);
        $data = [
            'enabled' => $enabled,
            'issuer' => TwoFactorService::issuer(),
            'account' => $account !== '' ? $account : 'admin',
            'confirmed_at' => $user['two_factor_confirmed_at'] ?? null,
            'masked_secret' => $enabled ? TwoFactorService::maskSecret((string) ($user['two_factor_secret'] ?? '')) : '',
        ];

        if ($enabled) {
            return $data;
        }

        $secret = TwoFactorService::generateSecret();
        $data['secret'] = $secret;
        $data['secret_formatted'] = TwoFactorService::formatSecret($secret);
        $data['provisioning_uri'] = TwoFactorService::provisioningUri($secret, $data['account']);

        return $data;
    }
}