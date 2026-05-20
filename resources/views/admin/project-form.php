<?php
$pageTitle = !empty($project) ? 'Modifier le projet' : 'Nouveau projet';
$projectCollaborations = $projectCollaborations ?? [];
?>
<div class='panel'>
    <form class='form' method='post' enctype='multipart/form-data' action='<?= !empty($project) ? url('/admin/projects/' . $project['id']) : url('/admin/projects') ?>'>
        <?= csrf_field() ?>
        <?php if (!empty($project)): ?><?= method_field('PUT') ?><?php endif; ?>

        <div class='form-row'>
            <label><span class='label'>Titre</span><input class='input' type='text' name='titre' value='<?= e($project['titre'] ?? '') ?>' required></label>
            <label><span class='label'>Slug</span><input class='input' type='text' name='slug' value='<?= e($project['slug'] ?? '') ?>'></label>
        </div>

        <div class='form-row'>
            <label><span class='label'>Statut</span><select class='select' name='statut'><option value='brouillon' <?= (($project['statut'] ?? '') === 'brouillon') ? 'selected' : '' ?>>Brouillon</option><option value='publie' <?= (($project['statut'] ?? '') === 'publie') ? 'selected' : '' ?>>Publie</option></select></label>
            <label><span class='label'>Ordre</span><input class='input' type='number' name='ordre' value='<?= e((string) ($project['ordre'] ?? 0)) ?>'></label>
        </div>

        <label><span class='label'>Description</span><textarea class='textarea' name='description'><?= e($project['description'] ?? '') ?></textarea></label>
        <label><span class='label'>Contenu</span><textarea class='textarea' name='contenu' data-autogrow><?= e($project['contenu'] ?? '') ?></textarea></label>
        <label><span class='label'>Technologies</span><input class='input' type='text' name='technologies' value='<?= e($project['technologies'] ?? '') ?>'></label>

        <div class='form-row'>
            <label><span class='label'>Image URL</span><input class='input' type='text' name='image_url' value='<?= e($project['image_url'] ?? '') ?>'></label>
            <label><span class='label'>Image couverture</span><input class='input' type='file' name='image' accept='.jpg,.jpeg,.png,.webp,.gif'></label>
        </div>

        <label><span class='label'>Galerie du projet</span><input class='input' type='file' name='gallery[]' accept='.jpg,.jpeg,.png,.webp,.gif' multiple data-preview-target='project-gallery-preview'></label>
        <?php $galleryImages = decode_json_array($project['gallery_images'] ?? null); ?>
        <?php if (!empty($project['image_url']) || !empty($galleryImages)): ?>
            <div class='stack-list'>
                <?php if (!empty($project['image_url'])): ?>
                    <div class='mini-card'>
                        <strong>Couverture actuelle</strong>
                        <img src='<?= e(url('/' . ltrim((string) $project['image_url'], '/'))) ?>' alt='Couverture du projet' style='margin-top:10px;max-height:180px;object-fit:cover;border-radius:16px;'>
                    </div>
                <?php endif; ?>
                <?php if (!empty($galleryImages)): ?>
                    <div class='mini-card'>
                        <strong>Images deja enregistrees</strong>
                        <div class='grid grid-3' style='margin-top:12px;'>
                            <?php foreach ($galleryImages as $image): ?>
                                <label class='card' style='padding:12px;'>
                                    <img src='<?= e(url('/' . ltrim($image, '/'))) ?>' alt='Image du projet' style='height:120px;width:100%;object-fit:cover;border-radius:12px;margin-bottom:10px;'>
                                    <span class='meta'><input type='checkbox' name='remove_gallery_images[]' value='<?= e($image) ?>'> Retirer</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div id='project-gallery-preview' class='grid grid-3'></div>

        <div class='form-row'>
            <label><span class='label'>GitHub URL</span><input class='input' type='url' name='github_url' value='<?= e($project['github_url'] ?? '') ?>'></label>
            <label><span class='label'>Demo URL</span><input class='input' type='url' name='demo_url' value='<?= e($project['demo_url'] ?? '') ?>'></label>
        </div>

        <div class='form-row'>
            <label><span class='label'>Mise en avant</span><select class='select' name='est_mis_en_avant'><option value='0' <?= !empty($project) && empty($project['est_mis_en_avant']) ? 'selected' : '' ?>>Non</option><option value='1' <?= !empty($project['est_mis_en_avant']) ? 'selected' : '' ?>>Oui</option></select></label>
        </div>

        <div class='section-head compact-head' style='margin-top:28px;'>
            <div>
                <div class='kicker'>Collaboration</div>
                <h2>Ajouter un collaborateur</h2>
            </div>
        </div>
        <p class='meta'>Laisse cette section vide si le projet a ete realise seul. Si tu la remplis, la personne sera mentionnee sur la fiche du projet.</p>

        <?php if (!empty($projectCollaborations)): ?>
            <div class='stack-list' style='margin-bottom:18px;'>
                <strong>Collaborateurs deja lies a ce projet</strong>
                <?php foreach ($projectCollaborations as $collaboration): ?>
                    <div class='mini-card'>
                        <div class='split-line'><strong><?= e($collaboration['nom_membre']) ?></strong><span class='meta'><?= e($collaboration['role'] ?? '') ?></span></div>
                        <?php if (!empty($collaboration['contribution'])): ?><p class='meta'><?= e($collaboration['contribution']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class='form-row'>
            <label><span class='label'>Projet en collaboration ?</span><select class='select' name='has_collaboration' data-collaboration-toggle><option value='0' selected>Non</option><option value='1'>Oui</option></select></label>
        </div>

        <div class='collaboration-fields' data-collaboration-fields hidden>
            <div class='form-row'>
                <label><span class='label'>Nom du collaborateur</span><input class='input' type='text' name='collaboration_nom_membre' data-collaboration-input disabled></label>
                <label><span class='label'>Role du collaborateur</span><input class='input' type='text' name='collaboration_role' data-collaboration-input disabled></label>
            </div>

            <div class='form-row'>
                <label><span class='label'>Email</span><input class='input' type='email' name='collaboration_email' data-collaboration-input disabled></label>
                <label><span class='label'>Portfolio URL</span><input class='input' type='url' name='collaboration_portfolio_url' data-collaboration-input disabled></label>
            </div>

            <div class='form-row'>
                <label><span class='label'>GitHub URL</span><input class='input' type='url' name='collaboration_github_url' data-collaboration-input disabled></label>
                <label><span class='label'>LinkedIn URL</span><input class='input' type='url' name='collaboration_linkedin_url' data-collaboration-input disabled></label>
            </div>

            <div class='form-row'>
                <label><span class='label'>Contribution</span><textarea class='textarea' name='collaboration_contribution' data-collaboration-input disabled></textarea></label>
            </div>
        </div>

        <p class='meta'>Tu pourras ajouter d'autres collaborateurs plus tard depuis la page Collaborations.</p>

        <div class='actions'>
            <button class='btn' type='submit'>Enregistrer</button>
            <a class='btn ghost' href='<?= url('/admin/projects') ?>'>Retour</a>
        </div>
    </form>
</div>
