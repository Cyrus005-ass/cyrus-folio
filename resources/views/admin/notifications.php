<?php $pageTitle = 'Notifications'; ?>
<div class='section-head'>
    <div><div class='kicker'>Centre</div><h2>Notifications</h2></div>
    <form method='post' action='<?= url('/admin/notifications/read-all') ?>'>
        <?= csrf_field() ?>
        <button class='btn ghost' type='submit'>Tout marquer lu</button>
    </form>
</div>
<div class='table-wrap'>
    <table>
        <thead><tr><th>Titre</th><th>Message</th><th>Etat</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <tr style='<?= empty($notification['is_read']) ? 'background:#eff6ff;' : '' ?>'>
                        <td><a href='<?= url('/admin/notifications/' . $notification['id'] . '/open') ?>'><strong><?= e($notification['titre']) ?></strong></a></td>
                        <td><?= e($notification['message'] ?? '') ?><div class='meta'><?= e($notification['lien'] ?? '') ?></div></td>
                        <td><?= !empty($notification['is_read']) ? 'Lue' : 'Non lue' ?></td>
                        <td>
                            <div class='actions'>
                                <a class='btn ghost' href='<?= url('/admin/notifications/' . $notification['id'] . '/open') ?>'>Ouvrir</a>
                                <?php if (empty($notification['is_read'])): ?>
                                    <form method='post' action='<?= url('/admin/notifications/' . $notification['id'] . '/read') ?>'>
                                        <?= csrf_field() ?>
                                        <?= method_field('PUT') ?>
                                        <button class='btn ghost' type='submit'>Marquer lue</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='4'><div class='empty'>Aucune notification.</div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
