<?php $pageTitle = 'Messages'; ?>
<div class='table-wrap'>
    <table>
        <thead><tr><th>Expediteur</th><th>Sujet</th><th>Date</th><th>Etat</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <tr style='<?= (($message['statut'] ?? 'nouveau') !== 'lu') ? 'background:#fff7ed;' : '' ?>'>
                        <td><strong><?= e($message['nom']) ?></strong><div class='meta'><?= e($message['email']) ?></div></td>
                        <td><?= e($message['sujet'] ?? '') ?><div class='meta'><?= e(excerpt($message['message'] ?? '', 90)) ?></div></td>
                        <td><?= e($message['created_at'] ?? '') ?></td>
                        <td><?php if (($message['statut'] ?? 'nouveau') === 'lu'): ?><span class='badge green'>Lu</span><?php else: ?><span class='badge red'>Non lu</span><?php endif; ?></td>
                        <td>
                            <div class='actions'>
                                <a class='btn ghost' href='<?= url('/admin/messages/' . $message['id']) ?>'>Lire</a>
                                <a class='btn ghost' href='mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: ' . ($message['sujet'] ?? '')) ?>'>Repondre</a>
                                <?php if (($message['statut'] ?? 'nouveau') !== 'lu'): ?>
                                    <form method='post' action='<?= url('/admin/messages/' . $message['id'] . '/read') ?>'>
                                        <?= csrf_field() ?>
                                        <?= method_field('PUT') ?>
                                        <button class='btn ghost' type='submit'>Marquer lu</button>
                                    </form>
                                <?php endif; ?>
                                <form method='post' action='<?= url('/admin/messages/' . $message['id']) ?>'>
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button class='btn danger' type='submit' data-confirm='Supprimer ce message ?'>Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='5'><div class='empty'>Aucun message.</div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
