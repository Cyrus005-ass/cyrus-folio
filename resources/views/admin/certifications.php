<?php $pageTitle = 'Certifications'; ?>
<div class='section-head'>
    <div><div class='kicker'>Administration</div><h2>Certifications</h2></div>
    <a class='btn' href='<?= url('/admin/certifications/create') ?>'>Nouvelle certification</a>
</div>
<div class='panel'>
    <form class='form' method='get' action='<?= url('/admin/certifications') ?>'>
        <div class='form-row'>
            <label><span class='label'>Statut</span><select class='select' name='status'><option value=''>Tous</option><option value='active' <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Actives</option><option value='expiring' <?= (($filters['status'] ?? '') === 'expiring') ? 'selected' : '' ?>>Expire bientot</option><option value='expired' <?= (($filters['status'] ?? '') === 'expired') ? 'selected' : '' ?>>Expirees</option><option value='hidden' <?= (($filters['status'] ?? '') === 'hidden') ? 'selected' : '' ?>>Masquees</option></select></label>
        </div>
        <div class='actions'><button class='btn ghost' type='submit'>Filtrer</button><a class='btn ghost' href='<?= url('/admin/certifications') ?>'>Reinitialiser</a></div>
    </form>
</div>
<div class='table-wrap' style='margin-top:20px;'>
    <table>
        <thead><tr><th>Titre</th><th>Organisme</th><th>Liens</th><th>Validite</th><th>Affichage</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if (!empty($certifications)): ?>
                <?php foreach ($certifications as $certification): ?>
                    <?php
                    $status = 'active';
                    if (empty($certification['est_active'])) {
                        $status = 'hidden';
                    } elseif (!empty($certification['date_expiration']) && $certification['date_expiration'] < date('Y-m-d')) {
                        $status = 'expired';
                    } elseif (!empty($certification['date_expiration']) && $certification['date_expiration'] <= date('Y-m-d', strtotime('+30 days'))) {
                        $status = 'expiring';
                    }
                    ?>
                    <tr>
                        <td><?= e($certification['titre']) ?></td>
                        <td><?= e($certification['organisme'] ?? '') ?></td>
                        <td>
                            <div class='stack-list'>
                                <?php if (!empty($certification['badge_url'])): ?><a href='<?= e($certification['badge_url']) ?>' target='_blank' rel='noreferrer'>Voir le badge</a><?php endif; ?>
                                <?php if (!empty($certification['lien_verification'])): ?><a href='<?= e($certification['lien_verification']) ?>' target='_blank' rel='noreferrer'>Lien de verification</a><?php endif; ?>
                                <?php if (empty($certification['badge_url']) && empty($certification['lien_verification'])): ?><span class='meta'>Aucun lien</span><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($status === 'active'): ?><span class='badge green'>Valide</span><?php endif; ?>
                            <?php if ($status === 'expiring'): ?><span class='badge blue'>Expire bientot</span><?php endif; ?>
                            <?php if ($status === 'expired'): ?><span class='badge red'>Expiree</span><?php endif; ?>
                            <?php if ($status === 'hidden'): ?><span class='badge'>Masquee</span><?php endif; ?>
                            <div class='meta'><?= e($certification['date_expiration'] ?? 'Sans expiration') ?></div>
                        </td>
                        <td><?= !empty($certification['est_active']) ? 'Publique' : 'Masquee' ?></td>
                        <td>
                            <div class='actions'>
                                <a class='btn ghost' href='<?= url('/admin/certifications/' . $certification['id'] . '/edit') ?>'>Modifier</a>
                                <form method='post' action='<?= url('/admin/certifications/' . $certification['id']) ?>'>
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button class='btn danger' type='submit' data-confirm='Supprimer cette certification ?'>Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='6'><div class='empty'>Aucune certification enregistree.</div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
