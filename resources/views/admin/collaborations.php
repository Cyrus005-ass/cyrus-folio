<?php $pageTitle = 'Collaborations'; ?>
<section class='admin-grid'>
    <div class='panel'>
        <h2>Ajouter une collaboration</h2>
        <form class='form' method='post' action='<?= url('/admin/collaborations') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Projet</span><select class='select' name='project_id'><option value=''>Aucun</option><?php foreach ($projects as $project): ?><option value='<?= $project['id'] ?>'><?= e($project['titre']) ?></option><?php endforeach; ?></select></label>
            <label><span class='label'>Nom membre</span><input class='input' type='text' name='nom_membre' required></label>
            <label><span class='label'>Role</span><input class='input' type='text' name='role' required></label>
            <div class='form-row'>
                <label><span class='label'>Portfolio URL</span><input class='input' type='text' name='portfolio_url'></label>
                <label><span class='label'>GitHub URL</span><input class='input' type='text' name='github_url'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Email</span><input class='input' type='email' name='email'></label>
                <label><span class='label'>LinkedIn URL</span><input class='input' type='text' name='linkedin_url'></label>
            </div>
            <label><span class='label'>Contribution</span><textarea class='textarea' name='contribution'></textarea></label>
            <button class='btn' type='submit'>Ajouter</button>
        </form>
    </div>
    <div class='panel'>
        <h2>Vue rapide projets</h2>
        <div class='stack-list'>
            <div class='mini-card'><strong>Projets solo</strong><p class='meta'><?= count($soloProjects ?? []) ?> projet(s)</p></div>
            <div class='mini-card'><strong>Projets collaboratifs</strong><p class='meta'><?= count($collaborativeProjects ?? []) ?> projet(s)</p></div>
            <?php if (!empty($collaborativeProjects)): ?>
                <?php foreach ($collaborativeProjects as $project): ?>
                    <div class='mini-card'><strong><?= e($project['titre']) ?></strong><p class='meta'><?= (int) ($project['collaborators_count'] ?? 0) ?> collaborateur(s)</p></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($soloProjects)): ?>
                <?php foreach ($soloProjects as $project): ?>
                    <div class='mini-card'><strong><?= e($project['titre']) ?></strong><p class='meta'>Projet solo</p></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class='panel' style='margin-top:20px;'>
    <h2>Collaborateurs</h2>
    <?php if (!empty($collaborations)): ?>
        <div class='stack-list'>
            <?php foreach ($collaborations as $collaboration): ?>
                <form class='form card' method='post' action='<?= url('/admin/collaborations/' . $collaboration['id']) ?>' style='padding:16px;'>
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class='form-row'>
                        <label><span class='label'>Projet</span><select class='select' name='project_id'><option value=''>Aucun</option><?php foreach ($projects as $project): ?><option value='<?= $project['id'] ?>' <?= (int) ($collaboration['project_id'] ?? 0) === (int) $project['id'] ? 'selected' : '' ?>><?= e($project['titre']) ?></option><?php endforeach; ?></select></label>
                        <label><span class='label'>Nom membre</span><input class='input' type='text' name='nom_membre' value='<?= e($collaboration['nom_membre']) ?>' required></label>
                    </div>
                    <div class='form-row'>
                        <label><span class='label'>Role</span><input class='input' type='text' name='role' value='<?= e($collaboration['role'] ?? '') ?>' required></label>
                        <label><span class='label'>Email</span><input class='input' type='email' name='email' value='<?= e($collaboration['email'] ?? '') ?>'></label>
                    </div>
                    <div class='form-row'>
                        <label><span class='label'>Portfolio URL</span><input class='input' type='text' name='portfolio_url' value='<?= e($collaboration['portfolio_url'] ?? '') ?>'></label>
                        <label><span class='label'>GitHub URL</span><input class='input' type='text' name='github_url' value='<?= e($collaboration['github_url'] ?? '') ?>'></label>
                    </div>
                    <div class='form-row'>
                        <label><span class='label'>LinkedIn URL</span><input class='input' type='text' name='linkedin_url' value='<?= e($collaboration['linkedin_url'] ?? '') ?>'></label>
                        <label><span class='label'>Contribution</span><textarea class='textarea' name='contribution'><?= e($collaboration['contribution'] ?? '') ?></textarea></label>
                    </div>
                    <div class='actions'>
                        <button class='btn ghost' type='submit'>Mettre a jour</button>
                    </div>
                </form>
                <form method='post' action='<?= url('/admin/collaborations/' . $collaboration['id']) ?>'>
                    <?= csrf_field() ?>
                    <?= method_field('DELETE') ?>
                    <button class='btn danger' type='submit' data-confirm='Supprimer cette collaboration ?'>Supprimer</button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class='empty'>Aucune collaboration.</div>
    <?php endif; ?>
</section>
