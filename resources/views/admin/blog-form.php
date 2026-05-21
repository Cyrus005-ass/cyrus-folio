<?php $pageTitle = !empty($post) ? 'Modifier un article' : 'Nouvel article'; ?>
<div class='panel'>
    <form class='form' method='post' enctype='multipart/form-data' action='<?= !empty($post) ? url('/admin/blog/' . $post['id']) : url('/admin/blog') ?>'>
        <?= csrf_field() ?>
        <?php if (!empty($post)): ?><?= method_field('PUT') ?><?php endif; ?>
        <div class='form-row'>
            <label><span class='label'>Titre</span><input class='input' type='text' name='titre' value='<?= e($post['titre'] ?? '') ?>' required></label>
            <label><span class='label'>Slug</span><input class='input' type='text' name='slug' value='<?= e($post['slug'] ?? '') ?>'></label>
        </div>
        <div class='form-row'>
            <label><span class='label'>Categorie</span><input class='input' type='text' name='category' value='<?= e($post['category'] ?? 'autre') ?>'></label>
            <label><span class='label'>Tags</span><input class='input' type='text' name='tags' value='<?= e($post['tags'] ?? '') ?>'></label>
        </div>
        <label><span class='label'>Extrait</span><textarea class='textarea' name='extrait'><?= e($post['extrait'] ?? '') ?></textarea></label>
        <div class='stack-list'>
            <div class='actions' data-editor-toolbar='post-content'>
                <button class='btn ghost' type='button' data-editor-action='bold'>Gras</button>
                <button class='btn ghost' type='button' data-editor-action='italic'>Italique</button>
                <button class='btn ghost' type='button' data-editor-action='insertUnorderedList'>Liste</button>
                <button class='btn ghost' type='button' data-editor-action='createLink'>Lien</button>
                <button class='btn ghost' type='button' data-editor-action='insertImage'>Image</button>
            </div>
            <div class='input' data-rich-editor='post-content' contenteditable='true' style='min-height:260px;'><?= sanitize_rich_text($post['contenu'] ?? '') ?? '' ?></div>
            <textarea class='textarea' id='post-content' name='contenu' style='display:none;'><?= e($post['contenu'] ?? '') ?></textarea>
        </div>
        <div class='form-row'>
            <label><span class='label'>Image couverture URL</span><input class='input' type='text' name='image_url' value='<?= e($post['image_url'] ?? '') ?>'></label>
            <label><span class='label'>Image couverture fichier</span><input class='input' type='file' name='image' accept='.jpg,.jpeg,.png,.webp,.gif'></label>
        </div>
        <?php if (!empty($post['image_url'])): ?>
            <img src='<?= e(absolute_url($post['image_url'] ?? null) ?? '') ?>' alt='Couverture article' style='max-height:220px;object-fit:cover;border-radius:18px;'>
        <?php endif; ?>
        <div class='form-row'>
            <label><span class='label'>Statut</span><select class='select' name='statut'><option value='brouillon' <?= (($post['statut'] ?? '') === 'brouillon') ? 'selected' : '' ?>>Brouillon</option><option value='publie' <?= (($post['statut'] ?? '') === 'publie') ? 'selected' : '' ?>>Publie</option></select></label>
            <label><span class='label'>Date publication</span><input class='input' type='datetime-local' name='published_at' value='<?= !empty($post['published_at']) ? e(str_replace(' ', 'T', substr((string) $post['published_at'], 0, 16))) : '' ?>'></label>
        </div>
        <div class='actions'><button class='btn' type='submit'>Enregistrer</button><a class='btn ghost' href='<?= url('/admin/blog') ?>'>Retour</a></div>
    </form>
</div>

