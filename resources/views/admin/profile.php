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
                <label><span class='label'>Telephone</span><input class='input' type='text' name='phone' value='<?= e($profile['phone'] ?? '') ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Localisation</span><input class='input' type='text' name='location' value='<?= e($profile['location'] ?? '') ?>'></label>
                <label><span class='label'>Disponibilite</span><select class='select' name='availability'><?php foreach (availability_options() as $status): ?><option value='<?= e($status) ?>' <?= (($profile['availability'] ?? 'disponible') === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Avatar URL</span><input class='input' type='text' name='avatar_url' value='<?= e($profile['avatar_url'] ?? '') ?>'></label>
                <label><span class='label'>CV URL</span><input class='input' type='text' name='cv_url' value='<?= e($profile['cv_url'] ?? '') ?>'></label>
            </div>
            <label><span class='label'>Video de presentation</span><input class='input' type='text' name='presentation_video_url' value='<?= e($profile['presentation_video_url'] ?? '') ?>' placeholder='https://www.youtube.com/watch?v=...'></label>
            <p class='meta' style='margin-top:-6px;'>Cette video reste rattachee a ton profil. Elle n'est plus proposee dans les autres modules.</p>
            <div class='form-row'>
                <label><span class='label'>Avatar fichier</span><input class='input' type='file' name='avatar' accept='.jpg,.jpeg,.png,.webp' data-preview-target='profile-avatar-preview'></label>
                <label><span class='label'>CV fichier</span><input class='input' type='file' name='cv' accept='.pdf,application/pdf'></label>
            </div>
            <?php if (!empty($profile['avatar_url'])): ?><img id='profile-avatar-preview' src='<?= e(url('/' . ltrim((string) $profile['avatar_url'], '/'))) ?>' alt='Avatar actuel' style='max-width:180px;border-radius:20px;object-fit:cover;'><?php else: ?><img id='profile-avatar-preview' alt='Apercu avatar' style='display:none;max-width:180px;border-radius:20px;object-fit:cover;'><?php endif; ?>
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
            <button class='btn secondary' type='submit'>Mettre a jour</button>
        </form>
    </div>
</section>
