<?php $pageTitle = 'Messages'; ?>
<?php $messageCount = is_array($messages ?? null) ? count($messages) : 0; ?>
<section
    class='messages-live-shell'
    data-live-messages
    data-live-endpoint='<?= e(url('/api/v1/messages?limit=50')) ?>'
    data-admin-base='<?= e(url('/admin/messages')) ?>'
    data-csrf='<?= e(csrf_token()) ?>'
    data-live-interval='30000'
>
    <div class='panel messages-live-panel'>
        <div class='messages-live-head'>
            <div>
                <div class='kicker'>Archive + live</div>
                <h2>Messagerie synchronisee</h2>
                <p class='meta' data-live-summary><?= $messageCount > 0 ? e($messageCount . ' message(s) archives charges. Le flux live Firestore va se synchroniser automatiquement.') : 'Aucun message archive pour le moment. Le flux live Firestore prendra le relais des qu un nouveau message arrivera.' ?></p>
            </div>
            <div class='actions messages-live-actions'>
                <span class='badge blue' data-live-status>Archive chargee</span>
                <span class='badge' data-live-last-sync>Connexion live en attente</span>
                <button class='btn ghost' type='button' data-live-refresh>Rafraichir</button>
            </div>
        </div>
    </div>

    <div class='table-wrap messages-table-wrap'>
        <table class='messages-table'>
            <thead><tr><th>Expediteur</th><th>Sujet</th><th>Date</th><th>Etat</th><th>Flux</th><th>Actions</th></tr></thead>
            <tbody data-live-messages-body>
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <?php $isUnread = (($message['statut'] ?? 'nouveau') !== 'lu'); ?>
                        <tr class='<?= $isUnread ? 'is-unread' : '' ?>' data-message-id='<?= e((string) ($message['id'] ?? '')) ?>'>
                            <td><strong><?= e($message['nom']) ?></strong><div class='meta'><?= e($message['email']) ?></div></td>
                            <td><?= e($message['sujet'] ?? '') ?><div class='meta'><?= e(excerpt($message['message'] ?? '', 90)) ?></div></td>
                            <td><?= e($message['created_at'] ?? '') ?></td>
                            <td><?php if (($message['statut'] ?? 'nouveau') === 'lu'): ?><span class='badge green'>Lu</span><?php else: ?><span class='badge red'>Non lu</span><?php endif; ?></td>
                            <td><span class='badge'>Archive</span></td>
                            <td>
                                <div class='actions'>
                                    <a class='btn ghost' href='<?= url('/admin/messages/' . $message['id']) ?>'>Lire</a>
                                    <a class='btn ghost' href='mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: ' . ($message['sujet'] ?? '')) ?>'>Repondre</a>
                                    <?php if ($isUnread): ?>
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
                    <tr><td colspan='6'><div class='empty'>Aucun message.</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
