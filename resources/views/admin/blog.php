<?php $pageTitle = 'Blog'; ?>
<div class='section-head'>
    <div><div class='kicker'>Administration</div><h2>Articles</h2></div>
    <a class='btn' href='<?= url('/admin/blog/create') ?>'>Nouvel article</a>
</div>
<div class='table-wrap'>
    <table>
        <thead><tr><th>Titre</th><th>Categorie</th><th>Statut</th><th>Publication</th><th>Vues</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= e($post['titre']) ?></td>
                        <td><?= e($post['category'] ?? 'autre') ?></td>
                        <td><?= e($post['statut'] ?? 'brouillon') ?></td>
                        <td><?= e($post['published_at'] ?? '') ?></td>
                        <td><?= (int) ($post['view_count'] ?? 0) ?></td>
                        <td>
                            <div class='actions'>
                                <a class='btn ghost' href='<?= url('/admin/blog/' . $post['id'] . '/edit') ?>'>Modifier</a>
                                <form method='post' action='<?= url('/admin/blog/' . $post['id']) ?>'>
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button class='btn danger' type='submit' data-confirm='Supprimer cet article ?'>Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='6'><div class='empty'>Aucun article.</div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
