<?php $pageTitle = 'Analytiques'; ?>
<div class='section-head'>
    <div><div class='kicker'>Mesure</div><h2>Analytiques</h2></div>
    <form method='get' action='<?= url('/admin/analytics') ?>' class='actions'>
        <button class='btn ghost' type='submit' name='period' value='7'>7 jours</button>
        <button class='btn ghost' type='submit' name='period' value='30'>30 jours</button>
        <button class='btn ghost' type='submit' name='period' value='90'>3 mois</button>
    </form>
</div>
<section class='stats'>
    <div class='stat'><span class='meta'>Visites totales</span><b><?= (int) ($summary['total_visits'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Visiteurs uniques</span><b><?= (int) ($summary['unique_visitors'] ?? 0) ?></b></div>
    <div class='stat'><span class='meta'>Duree moyenne</span><b><?= e(format_seconds_short((int) ($summary['avg_session_duration'] ?? 0))) ?></b></div>
    <div class='stat'><span class='meta'>Taux de rebond</span><b><?= number_format((float) ($summary['bounce_rate'] ?? 0), 2) ?>%</b></div>
    <div class='stat'><span class='meta'>Periode</span><b><?= (int) $period ?>j</b></div>
</section>
<section class='panel' style='margin-top:20px;'>
    <h2>Evolution journaliere</h2>
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
        <div class='empty'>Pas encore de donnees.</div>
    <?php endif; ?>
</section>
<section class='admin-grid' style='margin-top:20px;'>
    <div class='panel'>
        <h2>Pages les plus visitees</h2>
        <?php if (!empty($pages)): ?>
            <div class='stack-list'>
                <?php foreach ($pages as $page): ?>
                    <div class='split-line'><span><?= e($page['page']) ?></span><strong><?= (int) ($page['total'] ?? 0) ?> (<?= number_format((float) ($page['percentage'] ?? 0), 2) ?>%)</strong></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Pas encore de donnees.</div>
        <?php endif; ?>
    </div>
    <div class='panel'>
        <h2>Appareils</h2>
        <?php if (!empty($devices)): ?>
            <div class='stack-list'>
                <?php foreach ($devices as $device): ?>
                    <div class='split-line'><span><?= e($device['device']) ?></span><strong><?= (int) ($device['total'] ?? 0) ?></strong></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Pas encore de donnees.</div>
        <?php endif; ?>
    </div>
</section>
<section class='admin-grid' style='margin-top:20px;'>
    <div class='panel'>
        <h2>Carte geographique</h2>
        <div id='analytics-geo-map' class='geo-map' data-countries='<?= e(json_encode($countries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]') ?>'></div>
    </div>
    <div class='panel'>
        <h2>Top pays</h2>
        <?php if (!empty($countries)): ?>
            <div class='stack-list'>
                <?php foreach ($countries as $country): ?>
                    <div class='split-line'><span><?= e($country['country']) ?></span><strong><?= (int) ($country['total'] ?? 0) ?></strong></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune donnee geographique exploitable pour le moment.</div>
        <?php endif; ?>
    </div>
</section>
