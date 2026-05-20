<?php $pageTitle = 'Projets'; ?>
<div class='section-head'>
    <div>
        <div class='kicker'>Administration</div>
        <h2>Projets</h2>
    </div>
    <a class='btn' href='<?= url('/admin/projects/create') ?>'>Nouveau projet</a>
</div>

<div class='table-wrap'>
    <table>
        <thead>
            <tr><th>Titre</th><th>Statut</th><th>Ordre</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <strong><?= e($project['titre']) ?></strong>
                            <div class='meta'><?= e($project['slug'] ?? '') ?></div>
                        </td>
                        <td><?= e($project['statut'] ?? 'brouillon') ?></td>
                        <td><?= (int) ($project['ordre'] ?? 0) ?></td>
                        <td>
                            <div class='actions'>
                                <a class='btn ghost' href='<?= url('/admin/projects/' . $project['id'] . '/edit') ?>'>Modifier</a>
                                <form method='post' action='<?= url('/admin/projects/' . $project['id']) ?>'>
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button class='btn danger' type='submit' data-confirm='Supprimer ce projet ?'>Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='4'><div class='empty'>Aucun projet enregistre.</div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
