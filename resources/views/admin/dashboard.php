<?php $pageTitle = 'Dashboard'; ?>
<section class='stats'>
    <div class='stat'><span class='meta'>Visiteurs du mois</span><b><?= (int) ($counts['visitors_month'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Projets publies</span><b><?= (int) ($counts['published_projects'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Messages non lus</span><b><?= (int) ($counts['unread_messages'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Certifications actives</span><b><?= (int) ($counts['active_certifications'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Duree moyenne</span><b><?= e(format_seconds_short((int) ($analyticsSummary['avg_session_duration'] ?? 0))) ?></b></div>
</section>

<section class='panel' style='margin-top:20px;'>
    <div class='section-head compact-head'>
        <div><h2>Visites sur 30 jours</h2><p class='meta'>Evolution quotidienne des visites et visiteurs uniques.</p></div>
    </div>
    <?php if (!empty($timeline)): ?>
        <?php $maxVisits = max(array_map(fn ($item) => (int) ($item['visits'] ?? 0), $timeline)) ?: 1; ?>
        <div class='chart-bars'>
            <?php foreach ($timeline as $point): ?>
                <div class='chart-bar'>
                    <div class='chart-bar-fill' style='height:<?= max(8, (int) round(((int) ($point['visits'] ?? 0) / $maxVisits) * 140)) ?>px;'></div>
                    <span><?= e(substr((string) $point['day'], 5)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class='empty'>Pas encore de donnees de visite.</div>
    <?php endif; ?>
</section>

<section class='admin-grid' style='margin-top:20px;'>
    <div class='panel'>
        <h2>Alertes prioritaires</h2>
        <div class='stack-list'>
            <?php if (!empty($expiringCertifications)): ?>
                <?php foreach ($expiringCertifications as $certification): ?>
                    <div class='mini-card'><strong><?= e($certification['titre']) ?></strong><p class='meta'>Expire le <?= e($certification['date_expiration'] ?? '') ?></p></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($oldUnreadMessages)): ?>
                <?php foreach ($oldUnreadMessages as $message): ?>
                    <div class='mini-card'><strong><?= e($message['nom']) ?></strong><p class='meta'>Message non lu depuis plus de <?= (int) $messageAlertDays ?> jour(s)</p></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (empty($expiringCertifications) && empty($oldUnreadMessages)): ?>
                <div class='empty'>Aucune alerte immediate.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class='panel'>
        <h2>Analyse rapide</h2>
        <div class='stack-list'>
            <p><strong>Visites totales :</strong> <?= (int) ($analyticsSummary['total_visits'] ?? 0) ?></p>
            <p><strong>Visiteurs uniques :</strong> <?= (int) ($analyticsSummary['unique_visitors'] ?? 0) ?></p>
            <p><strong>Taux de rebond :</strong> <?= number_format((float) ($analyticsSummary['bounce_rate'] ?? 0), 2) ?>%</p>
        </div>
    </div>
</section>

<section class='admin-grid' style='margin-top:20px;'>
    <div class='panel'>
        <h2>Notifications recentes</h2>
        <?php if (!empty($notifications)): ?>
            <div class='stack-list'>
                <?php foreach ($notifications as $notification): ?>
                    <a class='mini-card' href='<?= url('/admin/notifications/' . $notification['id'] . '/open') ?>'>
                        <strong><?= e($notification['titre']) ?></strong>
                        <p class='meta'><?= e($notification['message'] ?? '') ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune notification.</div>
        <?php endif; ?>
    </div>
    <div class='panel'>
        <h2>Activites recentes</h2>
        <?php if (!empty($activities)): ?>
            <div class='stack-list'>
                <?php foreach ($activities as $activity): ?>
                    <div class='mini-card'>
                        <strong><?= e($activity['action']) ?></strong>
                        <p class='meta'><?= e($activity['description'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune activite.</div>
        <?php endif; ?>
    </div>
</section>
