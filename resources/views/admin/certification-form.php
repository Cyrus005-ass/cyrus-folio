<?php $pageTitle = !empty($certification) ? 'Modifier la certification' : 'Nouvelle certification'; ?>
<div class='panel'>
    <form class='form' method='post' action='<?= !empty($certification) ? url('/admin/certifications/' . $certification['id']) : url('/admin/certifications') ?>'>
        <?= csrf_field() ?>
        <?php if (!empty($certification)): ?><?= method_field('PUT') ?><?php endif; ?>

        <div class='form-row'>
            <label><span class='label'>Titre</span><input class='input' type='text' name='titre' value='<?= e($certification['titre'] ?? '') ?>' required></label>
            <label><span class='label'>Organisme</span><input class='input' type='text' name='organisme' value='<?= e($certification['organisme'] ?? '') ?>' required></label>
        </div>

        <div class='form-row'>
            <label><span class='label'>Date obtention</span><input class='input' type='date' name='date_obtention' value='<?= e($certification['date_obtention'] ?? date('Y-m-d')) ?>'></label>
            <label><span class='label'>Date expiration</span><input class='input' type='date' name='date_expiration' value='<?= e($certification['date_expiration'] ?? '') ?>'></label>
        </div>

        <div class='form-row'>
            <label><span class='label'>Credential ID</span><input class='input' type='text' name='credential_id' value='<?= e($certification['credential_id'] ?? '') ?>'></label>
            <label><span class='label'>Ordre</span><input class='input' type='number' name='ordre' value='<?= (int) ($certification['ordre'] ?? 0) ?>'></label>
        </div>

        <div class='form-row'>
            <label><span class='label'>Lien du badge</span><input class='input' type='url' name='badge_url' value='<?= e($certification['badge_url'] ?? '') ?>' placeholder='https://...'></label>
            <label><span class='label'>Lien de verification</span><input class='input' type='url' name='lien_verification' value='<?= e($certification['lien_verification'] ?? '') ?>' placeholder='https://...'></label>
        </div>

        <p class='meta'>Lien du badge : image ou page officielle du badge. Lien de verification : URL publique qui permet de prouver que la certification est bien valide.</p>

        <div class='form-row'>
            <label><span class='label'>Active</span><select class='select' name='est_active'><option value='1' <?= !empty($certification['est_active']) ? 'selected' : '' ?>>Oui</option><option value='0' <?= isset($certification['est_active']) && !$certification['est_active'] ? 'selected' : '' ?>>Non</option></select></label>
        </div>

        <div class='actions'><button class='btn' type='submit'>Enregistrer</button><a class='btn ghost' href='<?= url('/admin/certifications') ?>'>Retour</a></div>
    </form>
</div>
