<?php $pageTitle = 'Comp?tences'; ?>
<section class='admin-grid'>
    <div class='panel'>
        <h2>Ajouter une comp?tence</h2>
        <form class='form' method='post' action='<?= url('/admin/skills') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Nom</span><input class='input' type='text' name='nom' required></label>
            <div class='form-row'>
                <label><span class='label'>Cat?gorie</span><select class='select' name='categorie'><?php foreach (skill_category_options() as $category): ?><option value='<?= e($category) ?>'><?= e(skill_category_label($category)) ?></option><?php endforeach; ?></select></label>
                <label><span class='label'>Niveau</span><select class='select' name='niveau'><?php foreach (skill_level_options() as $level): ?><option value='<?= e($level) ?>' <?= $level === 'Intermediaire' ? 'selected' : '' ?>><?= e(skill_level_label($level)) ?></option><?php endforeach; ?></select></label>
            </div>
            <label><span class='label'>Description</span><textarea class='textarea' name='description'></textarea></label>
            <div class='form-row'>
                <label><span class='label'>Ic?ne</span><input class='input' type='text' name='icone'></label>
                <label><span class='label'>Ordre</span><input class='input' type='number' name='ordre' value='0'></label>
            </div>
            <label><span class='label'>Active</span><select class='select' name='est_active'><option value='1'>Oui</option><option value='0'>Non</option></select></label>
            <button class='btn' type='submit'>Ajouter</button>
        </form>
    </div>
    <div class='panel'>
        <h2>Liste des comp?tences</h2>
        <?php if (!empty($skills)): ?>
            <?php $grouped = []; foreach ($skills as $skill) { $grouped[$skill['categorie']][] = $skill; } ?>
            <div class='stack-list'>
                <?php foreach ($grouped as $category => $items): ?>
                    <div class='mini-card'>
                        <h3><?= e(skill_category_label($category)) ?></h3>
                        <div class='stack-list'>
                            <?php foreach ($items as $skill): ?>
                                <form class='form card' method='post' action='<?= url('/admin/skills/' . $skill['id']) ?>' style='padding:16px;'>
                                    <?= csrf_field() ?>
                                    <?= method_field('PUT') ?>
                                    <div class='form-row'>
                                        <label><span class='label'>Nom</span><input class='input' type='text' name='nom' value='<?= e($skill['nom']) ?>' required></label>
                                        <label><span class='label'>Cat?gorie</span><select class='select' name='categorie'><?php foreach (skill_category_options() as $option): ?><option value='<?= e($option) ?>' <?= ($skill['categorie'] ?? '') === $option ? 'selected' : '' ?>><?= e(skill_category_label($option)) ?></option><?php endforeach; ?></select></label>
                                    </div>
                                    <div class='form-row'>
                                        <label><span class='label'>Niveau</span><select class='select' name='niveau'><?php foreach (skill_level_options() as $level): ?><option value='<?= e($level) ?>' <?= ($skill['niveau'] ?? '') === $level ? 'selected' : '' ?>><?= e(skill_level_label($level)) ?></option><?php endforeach; ?></select></label>
                                        <label><span class='label'>Ordre</span><input class='input' type='number' name='ordre' value='<?= (int) ($skill['ordre'] ?? 0) ?>'></label>
                                    </div>
                                    <label><span class='label'>Description</span><textarea class='textarea' name='description'><?= e($skill['description'] ?? '') ?></textarea></label>
                                    <div class='form-row'>
                                        <label><span class='label'>Ic?ne</span><input class='input' type='text' name='icone' value='<?= e($skill['icone'] ?? '') ?>'></label>
                                        <label><span class='label'>Active</span><select class='select' name='est_active'><option value='1' <?= !empty($skill['est_active']) ? 'selected' : '' ?>>Oui</option><option value='0' <?= isset($skill['est_active']) && !$skill['est_active'] ? 'selected' : '' ?>>Non</option></select></label>
                                    </div>
                                    <div class='actions'>
                                        <button class='btn ghost' type='submit'>Mettre ? jour</button>
                                    </div>
                                </form>
                                <form method='post' action='<?= url('/admin/skills/' . $skill['id']) ?>'>
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button class='btn danger' type='submit' data-confirm='Supprimer cette comp?tence ?'>Supprimer</button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune comp?tence enregistr?e.</div>
        <?php endif; ?>
    </div>
</section>
