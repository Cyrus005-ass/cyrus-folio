<?php $pageTitle = 'Profil'; ?>
<section class='admin-grid'>
    <div class='panel'>
        <h2>Informations principales</h2>
        <form class='form' method='post' enctype='multipart/form-data' action='<?= url('/admin/profile') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Nom complet</span><input class='input' type='text' name='full_name' value='<?= e($profile['full_name'] ?? '') ?>' required></label>
            <label><span class='label'>Titre</span><input class='input' type='text' name='title' value='<?= e($profile['title'] ?? '') ?>'></label>
            <label><span class='label'>Bio</span><textarea class='textarea' name='bio'><?= e($profile['bio'] ?? '') ?></textarea></label>
            <div class='form-row'>
                <label><span class='label'>Email</span><input class='input' type='email' name='email' value='<?= e($profile['email'] ?? ($user['email'] ?? '')) ?>' required></label>
                <label><span class='label'>T?l?phone</span><input class='input' type='text' name='phone' value='<?= e($profile['phone'] ?? '') ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Localisation</span><input class='input' type='text' name='location' value='<?= e($profile['location'] ?? '') ?>'></label>
                <label><span class='label'>Disponibilit?</span><select class='select' name='availability'><?php foreach (availability_options() as $status): ?><option value='<?= e($status) ?>' <?= (($profile['availability'] ?? 'disponible') === $status) ? 'selected' : '' ?>><?= e(availability_label($status)) ?></option><?php endforeach; ?></select></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Avatar URL</span><input class='input' type='text' name='avatar_url' value='<?= e($profile['avatar_url'] ?? '') ?>'></label>
                <label><span class='label'>CV URL</span><input class='input' type='text' name='cv_url' value='<?= e($profile['cv_url'] ?? '') ?>'></label>
            </div>
            <label><span class='label'>Vid?o de pr?sentation</span><input class='input' type='text' name='presentation_video_url' value='<?= e($profile['presentation_video_url'] ?? '') ?>' placeholder='https://www.youtube.com/watch?v=...'></label>
            <p class='meta' style='margin-top:-6px;'>Cette vid?o reste rattach?e ? ton profil. Elle n'est plus propos?e dans les autres modules.</p>
            <div class='form-row'>
                <label><span class='label'>Avatar fichier</span><input class='input' type='file' name='avatar' accept='.jpg,.jpeg,.png,.webp' data-preview-target='profile-avatar-preview'></label>
                <label><span class='label'>CV fichier</span><input class='input' type='file' name='cv' accept='.pdf,application/pdf'></label>
            </div>
            <?php if (!empty($profile['avatar_url'])): ?><img id='profile-avatar-preview' src='<?= e(absolute_url($profile['avatar_url'] ?? null) ?? '') ?>' alt='Avatar actuel' style='max-width:180px;border-radius:20px;object-fit:cover;'><?php else: ?><img id='profile-avatar-preview' alt='Aper?u avatar' style='display:none;max-width:180px;border-radius:20px;object-fit:cover;'><?php endif; ?>
            <div class='form-row'>
                <label><span class='label'>GitHub URL</span><input class='input' type='text' name='github_url' value='<?= e($profile['github_url'] ?? '') ?>'></label>
                <label><span class='label'>LinkedIn URL</span><input class='input' type='text' name='linkedin_url' value='<?= e($profile['linkedin_url'] ?? '') ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Twitter/X URL</span><input class='input' type='text' name='twitter_url' value='<?= e($profile['twitter_url'] ?? '') ?>'></label>
                <label><span class='label'>Instagram URL</span><input class='input' type='text' name='instagram_url' value='<?= e($profile['instagram_url'] ?? '') ?>' placeholder='https://instagram.com/...'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>WhatsApp URL</span><input class='input' type='text' name='whatsapp_url' value='<?= e($profile['whatsapp_url'] ?? '') ?>' placeholder='https://wa.me/...'></label>
                <label><span class='label'>Facebook URL</span><input class='input' type='text' name='facebook_url' value='<?= e($profile['facebook_url'] ?? '') ?>' placeholder='https://facebook.com/...'></label>
            </div>
            <label><span class='label'>Website URL</span><input class='input' type='text' name='website_url' value='<?= e($profile['website_url'] ?? '') ?>'></label>
            <label><span class='label'>Autres liens</span><textarea class='textarea' name='other_links' placeholder='Label|https://exemple.com'><?= e($profile['other_links'] ?? '') ?></textarea></label>
            <button class='btn' type='submit'>Sauvegarder</button>
        </form>
    </div>

    <div class='panel'>
        <h2>Mot de passe</h2>
        <form class='form' method='post' action='<?= url('/admin/profile/password') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Ancien mot de passe</span><input class='input' type='password' name='old_password' required></label>
            <label><span class='label'>Nouveau mot de passe</span><input class='input' type='password' name='password' required></label>
            <label><span class='label'>Confirmation</span><input class='input' type='password' name='password_confirmation' required></label>
            <button class='btn secondary' type='submit'>Mettre ? jour</button>
        </form>
    </div>

    <div class='panel'>
        <h2>Authentification 2FA</h2>
        <?php if (!empty($twoFactor['enabled'])): ?>
            <div class='security-card'>
                <span class='badge green'>Active</span>
                <p>La v?rification par application TOTP est activ?e sur ce compte admin.</p>
                <p><strong>Application / issuer :</strong> <?= e($twoFactor['issuer'] ?? '') ?></p>
                <p><strong>Compte :</strong> <?= e($twoFactor['account'] ?? '') ?></p>
                <?php if (!empty($twoFactor['masked_secret'])): ?><p><strong>Secret m?moris? :</strong></p><div class='security-mono'><?= e($twoFactor['masked_secret']) ?></div><?php endif; ?>
                <?php if (!empty($twoFactor['confirmed_at'])): ?><p class='meta'>Activ?e le <?= e(date('d/m/Y H:i', strtotime((string) $twoFactor['confirmed_at']))) ?></p><?php endif; ?>
                <p class='meta'>Le remember me est volontairement coup? tant que la 2FA reste active, pour ?viter un contournement par cookie persistant.</p>
            </div>

            <form class='form' method='post' action='<?= url('/admin/profile/2fa/disable') ?>' style='margin-top:16px;'>
                <?= csrf_field() ?>
                <label><span class='label'>Mot de passe actuel</span><input class='input' type='password' name='current_password' required></label>
                <label><span class='label'>Code Authenticator</span><input class='input auth-code-input' type='text' name='code' inputmode='numeric' autocomplete='one-time-code' pattern='[0-9]*' maxlength='6' placeholder='123456' required></label>
                <button class='btn secondary' type='submit'>D?sactiver la 2FA</button>
            </form>
        <?php else: ?>
            <div class='security-card'>
                <span class='badge orange'>Inactive</span>
                <p>Active la 2FA avec une application comme Google Authenticator, Microsoft Authenticator ou Authy.</p>
                <p><strong>Compte :</strong> <?= e($twoFactor['account'] ?? '') ?></p>
                <p><strong>Issuer :</strong> <?= e($twoFactor['issuer'] ?? '') ?></p>
                <p><strong>Cl? manuelle :</strong></p>
                <div class='security-mono'><?= e($twoFactor['secret_formatted'] ?? '') ?></div>
                <p class='meta'>Ajoute un compte TOTP manuel avec cette cl?, puis entre le code g?n?r? ci-dessous pour confirmer l'activation.</p>
            </div>

            <form class='form' method='post' action='<?= url('/admin/profile/2fa/enable') ?>' style='margin-top:16px;'>
                <?= csrf_field() ?>
                <input type='hidden' name='two_factor_secret' value='<?= e($twoFactor['secret'] ?? '') ?>'>
                <label><span class='label'>Mot de passe actuel</span><input class='input' type='password' name='current_password' required></label>
                <label><span class='label'>Code g?n?r? par l'application</span><input class='input auth-code-input' type='text' name='code' inputmode='numeric' autocomplete='one-time-code' pattern='[0-9]*' maxlength='6' placeholder='123456' required></label>
                <button class='btn secondary' type='submit'>Activer la 2FA</button>
            </form>
        <?php endif; ?>
    </div>
</section>