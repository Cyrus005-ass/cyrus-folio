<?php
$pageTitle = 'Dashboard';
$formatDateTime = static function (?string $value): string {
    if (!is_string($value) || trim($value) === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
};
$healthBadgeMap = [
    'healthy' => ['label' => 'OK', 'class' => 'green'],
    'warning' => ['label' => 'Attention', 'class' => 'orange'],
    'danger' => ['label' => 'Critique', 'class' => 'red'],
    'info' => ['label' => 'Info', 'class' => 'blue'],
];
$healthSummary = is_array($systemHealth['summary'] ?? null) ? $systemHealth['summary'] : [];
?>
<section class='stats'>
    <div class='stat'><span class='meta'>Visiteurs du mois</span><b><?= (int) ($counts['visitors_month'] ?? 0) ?></b><small>Sessions uniques sur le mois en cours.</small></div>
    <div class='stat'><span class='meta'>Visites sur 30 jours</span><b><?= (int) ($counts['total_visits'] ?? 0) ?></b><small>Pages chargees sur la fenetre analytics active.</small></div>
    <div class='stat'><span class='meta'>Projets publies</span><b><?= (int) ($counts['published_projects'] ?? 0) ?></b><small>Contenu visible publiquement.</small></div>
    <div class='stat'><span class='meta'>File editoriale</span><b><?= (int) ($counts['editorial_backlog'] ?? 0) ?></b><small>Brouillons projets + blog a traiter.</small></div>
    <div class='stat'><span class='meta'>Messages non lus</span><b><?= (int) ($counts['unread_messages'] ?? 0) ?></b><small>Demandes a lire ou relancer.</small></div>
    <div class='stat'><span class='meta'>Notifications non lues</span><b><?= (int) ($counts['unread_notifications'] ?? 0) ?></b><small>Evenements systeme et metier.</small></div>
    <div class='stat'><span class='meta'>Certifications actives</span><b><?= (int) ($counts['active_certifications'] ?? 0) ?></b><small>Encore valides ou sans expiration.</small></div>
    <div class='stat'><span class='meta'>Base chatbot</span><b><?= (int) ($counts['knowledge_base'] ?? 0) ?></b><small>Fiches locales actives pour les reponses.</small></div>
</section>

<section class='panel dashboard-hero dashboard-section'>
    <div>
        <div class='kicker'>Cockpit admin</div>
        <h2>Un centre de pilotage plus utile pour suivre le contenu, les alertes et la sante technique.</h2>
        <p class='meta'>Le dashboard remonte les brouillons, l inbox, les signaux systeme critiques et les raccourcis qui font gagner du temps au quotidien.</p>
    </div>
    <div class='actions dashboard-quick-actions'>
        <?php foreach ($quickActions as $action): ?>
            <a class='btn<?= !empty($action['style']) ? ' ' . e((string) $action['style']) : '' ?>' href='<?= url((string) ($action['path'] ?? '/admin')) ?>'><?= e((string) ($action['label'] ?? 'Ouvrir')) ?></a>
        <?php endforeach; ?>
    </div>
    <div class='dashboard-health-summary'>
        <span class='badge green'><?= (int) ($healthSummary['healthy'] ?? 0) ?> OK</span>
        <span class='badge orange'><?= (int) ($healthSummary['warning'] ?? 0) ?> attention</span>
        <span class='badge red'><?= (int) ($healthSummary['danger'] ?? 0) ?> critique</span>
        <span class='badge blue'><?= (int) ($healthSummary['info'] ?? 0) ?> info</span>
        <span class='badge'>Duree moyenne <?= e(format_seconds_short((int) ($analyticsSummary['avg_session_duration'] ?? 0))) ?></span>
    </div>
</section>

<section class='panel dashboard-section'>
    <div class='section-head compact-head'>
        <div>
            <h2>Visites sur 30 jours</h2>
            <p class='meta'>Evolution quotidienne des visites et des visiteurs uniques pour garder un oeil sur la traction du portfolio.</p>
        </div>
    </div>
    <?php if (!empty($timeline)): ?>
        <?php $maxVisits = max(array_map(fn ($item) => (int) ($item['visits'] ?? 0), $timeline)) ?: 1; ?>
        <div class='chart-bars'>
            <?php foreach ($timeline as $point): ?>
                <div class='chart-bar'>
                    <div class='chart-bar-fill' style='height:<?= max(8, (int) round(((int) ($point['visits'] ?? 0) / $maxVisits) * 140)) ?>px;'></div>
                    <span><?= e(substr((string) ($point['day'] ?? ''), 5)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class='empty'>Pas encore de donnees de visite.</div>
    <?php endif; ?>
</section>

<section class='admin-grid dashboard-section'>
    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>File editoriale</h2>
                <p class='meta'>Les brouillons les plus recents a completer ou publier.</p>
            </div>
        </div>
        <div class='dashboard-split'>
            <div class='dashboard-subsection'>
                <div class='dashboard-subhead'>
                    <strong>Projets en brouillon</strong>
                    <a class='inline-link' href='<?= url('/admin/projects') ?>'>Ouvrir</a>
                </div>
                <?php if (!empty($draftProjects)): ?>
                    <div class='dashboard-list'>
                        <?php foreach ($draftProjects as $project): ?>
                            <?php $projectExcerpt = excerpt(((string) ($project['description'] ?? '')) !== '' ? (string) $project['description'] : (string) ($project['contenu'] ?? ''), 110); ?>
                            <a class='dashboard-list-item' href='<?= url('/admin/projects/' . $project['id'] . '/edit') ?>'>
                                <div class='dashboard-list-head'>
                                    <strong><?= e((string) ($project['titre'] ?? 'Projet sans titre')) ?></strong>
                                    <span class='badge'><?= e($formatDateTime($project['updated_at'] ?? $project['created_at'] ?? null)) ?></span>
                                </div>
                                <p class='meta'><?= e($projectExcerpt !== '' ? $projectExcerpt : 'Description a completer.') ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class='empty'>Aucun projet en brouillon.</div>
                <?php endif; ?>
            </div>
            <div class='dashboard-subsection'>
                <div class='dashboard-subhead'>
                    <strong>Articles en brouillon</strong>
                    <a class='inline-link' href='<?= url('/admin/blog') ?>'>Ouvrir</a>
                </div>
                <?php if (!empty($draftPosts)): ?>
                    <div class='dashboard-list'>
                        <?php foreach ($draftPosts as $post): ?>
                            <?php $postExcerpt = excerpt(((string) ($post['extrait'] ?? '')) !== '' ? (string) $post['extrait'] : (string) ($post['contenu'] ?? ''), 110); ?>
                            <a class='dashboard-list-item' href='<?= url('/admin/blog/' . $post['id'] . '/edit') ?>'>
                                <div class='dashboard-list-head'>
                                    <strong><?= e((string) ($post['titre'] ?? 'Article sans titre')) ?></strong>
                                    <span class='badge'><?= e($formatDateTime($post['updated_at'] ?? $post['created_at'] ?? null)) ?></span>
                                </div>
                                <p class='meta'><?= e($postExcerpt !== '' ? $postExcerpt : 'Extrait a completer.') ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class='empty'>Aucun article en brouillon.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>Inbox et alertes</h2>
                <p class='meta'>Ce qui merite une lecture ou une action rapide.</p>
            </div>
        </div>
        <div class='dashboard-subsection'>
            <div class='dashboard-subhead'>
                <strong>Messages non lus</strong>
                <a class='inline-link' href='<?= url('/admin/messages') ?>'>Voir la messagerie</a>
            </div>
            <?php if (!empty($recentUnreadMessages)): ?>
                <div class='dashboard-list'>
                    <?php foreach ($recentUnreadMessages as $message): ?>
                        <a class='dashboard-list-item' href='<?= url('/admin/messages/' . $message['id']) ?>'>
                            <div class='dashboard-list-head'>
                                <strong><?= e((string) ($message['nom'] ?? 'Expediteur inconnu')) ?></strong>
                                <span class='badge red'>Non lu</span>
                            </div>
                            <p class='meta'><?= e((string) ($message['sujet'] ?? 'Sans sujet')) ?> - <?= e($formatDateTime($message['created_at'] ?? null)) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class='empty'>Aucun message non lu.</div>
            <?php endif; ?>
        </div>

        <div class='dashboard-subsection'>
            <div class='dashboard-subhead'>
                <strong>Alertes prioritaires</strong>
                <span class='badge'>Fenetre <?= (int) $messageAlertDays ?> jour(s)</span>
            </div>
            <div class='dashboard-list'>
                <?php foreach ($expiringCertifications as $certification): ?>
                    <a class='dashboard-list-item' href='<?= url('/admin/certifications/' . $certification['id'] . '/edit') ?>'>
                        <div class='dashboard-list-head'>
                            <strong><?= e((string) ($certification['titre'] ?? 'Certification')) ?></strong>
                            <span class='badge orange'>Expire bientot</span>
                        </div>
                        <p class='meta'>Expiration prevue le <?= e((string) ($certification['date_expiration'] ?? '')) ?></p>
                    </a>
                <?php endforeach; ?>

                <?php foreach ($oldUnreadMessages as $message): ?>
                    <a class='dashboard-list-item' href='<?= url('/admin/messages/' . $message['id']) ?>'>
                        <div class='dashboard-list-head'>
                            <strong><?= e((string) ($message['nom'] ?? 'Message en attente')) ?></strong>
                            <span class='badge red'>A relancer</span>
                        </div>
                        <p class='meta'>Message non lu depuis plus de <?= (int) $messageAlertDays ?> jour(s).</p>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($expiringCertifications) && empty($oldUnreadMessages)): ?>
                    <div class='empty'>Aucune alerte immediate.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class='admin-grid dashboard-section'>
    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>Sante systeme</h2>
                <p class='meta'>Etat des briques critiques pour l admin, les uploads et les integrations.</p>
            </div>
        </div>
        <div class='dashboard-health-grid'>
            <?php foreach (($systemHealth['checks'] ?? []) as $check): ?>
                <?php $badge = $healthBadgeMap[(string) ($check['status'] ?? 'info')] ?? $healthBadgeMap['info']; ?>
                <div class='dashboard-health-card is-<?= e((string) ($check['status'] ?? 'info')) ?>'>
                    <div class='dashboard-list-head'>
                        <strong><?= e((string) ($check['title'] ?? 'Verification')) ?></strong>
                        <span class='badge <?= e((string) ($badge['class'] ?? 'blue')) ?>'><?= e((string) ($badge['label'] ?? 'Info')) ?></span>
                    </div>
                    <p class='meta'><?= e((string) ($check['detail'] ?? '')) ?></p>
                    <?php if (!empty($check['meta'])): ?>
                        <div class='dashboard-meta-mono'><?= e((string) $check['meta']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>Couverture des modules</h2>
                <p class='meta'>Un aper?u rapide de la charge et du niveau de contenu par section.</p>
            </div>
        </div>
        <div class='dashboard-module-grid'>
            <?php foreach ($moduleSnapshots as $module): ?>
                <a class='dashboard-module-card' href='<?= url((string) ($module['path'] ?? '/admin')) ?>'>
                    <span class='meta'><?= e((string) ($module['label'] ?? 'Module')) ?></span>
                    <strong><?= (int) ($module['total'] ?? 0) ?></strong>
                    <p class='meta'><?= e((string) ($module['detail'] ?? '')) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class='admin-grid dashboard-section'>
    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>Trafic recent</h2>
                <p class='meta'>Top pages, devices et pays sur les 30 derniers jours.</p>
            </div>
            <a class='inline-link' href='<?= url('/admin/analytics') ?>'>Analyse complete</a>
        </div>

        <div class='dashboard-inline-stats'>
            <div class='dashboard-inline-stat'><span class='meta'>Visiteurs uniques</span><strong><?= (int) ($analyticsSummary['unique_visitors'] ?? 0) ?></strong></div>
            <div class='dashboard-inline-stat'><span class='meta'>Taux de rebond</span><strong><?= number_format((float) ($analyticsSummary['bounce_rate'] ?? 0), 2) ?>%</strong></div>
            <div class='dashboard-inline-stat'><span class='meta'>Session moyenne</span><strong><?= e(format_seconds_short((int) ($analyticsSummary['avg_session_duration'] ?? 0))) ?></strong></div>
        </div>

        <div class='dashboard-split'>
            <div class='dashboard-subsection'>
                <div class='dashboard-subhead'>
                    <strong>Top pages</strong>
                    <span class='badge'>30 jours</span>
                </div>
                <?php if (!empty($traffic['topPages'])): ?>
                    <div class='dashboard-traffic-list'>
                        <?php foreach ($traffic['topPages'] as $page): ?>
                            <div class='dashboard-traffic-item'>
                                <div>
                                    <strong><?= e(excerpt((string) ($page['page'] ?? '/'), 40)) ?></strong>
                                    <p class='meta'><?= number_format((float) ($page['percentage'] ?? 0), 2) ?>% du trafic</p>
                                </div>
                                <span class='badge'><?= (int) ($page['total'] ?? 0) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class='empty'>Aucune page visitee pour l instant.</div>
                <?php endif; ?>
            </div>

            <div class='dashboard-subsection'>
                <div class='dashboard-subhead'>
                    <strong>Devices et pays</strong>
                    <span class='badge'>Snapshot</span>
                </div>
                <div class='dashboard-badge-list'>
                    <?php foreach (($traffic['devices'] ?? []) as $device): ?>
                        <span class='badge'><?= e(ucfirst((string) ($device['device'] ?? 'inconnu'))) ?> <?= (int) ($device['total'] ?? 0) ?></span>
                    <?php endforeach; ?>
                    <?php foreach (($traffic['countries'] ?? []) as $country): ?>
                        <span class='badge blue'><?= e((string) ($country['country'] ?? 'Inconnu')) ?> <?= (int) ($country['total'] ?? 0) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($traffic['devices']) && empty($traffic['countries'])): ?>
                    <div class='empty'>Les segments device et pays apparaitront apres les premieres visites tracees.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class='panel'>
        <div class='section-head compact-head'>
            <div>
                <h2>Notifications recentes</h2>
                <p class='meta'>Signalements systeme et rappels metier.</p>
            </div>
            <a class='inline-link' href='<?= url('/admin/notifications') ?>'>Tout ouvrir</a>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class='dashboard-list'>
                <?php foreach ($notifications as $notification): ?>
                    <a class='dashboard-list-item' href='<?= url('/admin/notifications/' . $notification['id'] . '/open') ?>'>
                        <div class='dashboard-list-head'>
                            <strong><?= e((string) ($notification['titre'] ?? 'Notification')) ?></strong>
                            <?php if (!empty($notification['is_read'])): ?>
                                <span class='badge'>Lue</span>
                            <?php else: ?>
                                <span class='badge red'>A lire</span>
                            <?php endif; ?>
                        </div>
                        <p class='meta'><?= e((string) ($notification['message'] ?? '')) ?></p>
                        <p class='meta'><?= e($formatDateTime($notification['created_at'] ?? null)) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune notification.</div>
        <?php endif; ?>
    </div>
</section>

<section class='panel dashboard-section'>
    <div class='section-head compact-head'>
        <div>
            <h2>Activites recentes</h2>
            <p class='meta'>Journal des actions admin et evenements traces.</p>
        </div>
    </div>
    <?php if (!empty($activities)): ?>
        <div class='dashboard-list'>
            <?php foreach ($activities as $activity): ?>
                <div class='dashboard-list-item'>
                    <div class='dashboard-list-head'>
                        <strong><?= e((string) ($activity['action'] ?? 'action')) ?></strong>
                        <span class='badge'><?= e($formatDateTime($activity['created_at'] ?? null)) ?></span>
                    </div>
                    <p class='meta'><?= e((string) ($activity['description'] ?? '')) ?></p>
                    <p class='meta'><?= e((string) (($activity['user_name'] ?? null) ?: 'Systeme')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class='empty'>Aucune activite.</div>
    <?php endif; ?>
</section>
