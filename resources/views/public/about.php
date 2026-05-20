<?php $socialLinks = profile_social_links($profile ?? null); ?>
<?php $otherLinks = parse_named_links($profile['other_links'] ?? ''); ?>
<?php $profileImage = absolute_url($profile['avatar_url'] ?? null) ?? ''; ?>
<?php $displayName = trim((string) (($profile['full_name'] ?? '') ?: 'Cyrus-y ASSOGBA')); ?>
<?php $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $displayName) ?: 'CA', 0, 2)); ?>
<?php $cvUrl = absolute_url($profile['cv_url'] ?? null) ?? ''; ?>
<?php $skills = is_array($skills ?? null) ? $skills : []; ?>
<?php $aboutStats = is_array($aboutStats ?? null) ? $aboutStats : []; ?>
<?php
$detailItems = [];
if (!empty($profile['email'])) {
    $detailItems[] = ['label' => 'Email', 'value' => (string) $profile['email'], 'href' => 'mailto:' . (string) $profile['email']];
}
if (!empty($profile['phone'])) {
    $phoneHref = preg_replace('/\s+/', '', (string) $profile['phone']) ?: '';
    $detailItems[] = ['label' => 'Telephone', 'value' => (string) $profile['phone'], 'href' => $phoneHref !== '' ? 'tel:' . $phoneHref : null];
}
if (!empty($profile['location'])) {
    $detailItems[] = ['label' => 'Localisation', 'value' => (string) $profile['location'], 'href' => null];
}
if (!empty($profile['availability'])) {
    $detailItems[] = ['label' => 'Disponibilite', 'value' => str_replace('_', ' ', (string) $profile['availability']), 'href' => null];
}
if (!empty($profile['website_url'])) {
    $detailItems[] = ['label' => 'Site web', 'value' => (string) $profile['website_url'], 'href' => (string) $profile['website_url']];
}

$networkLinks = [];
$seenUrls = [];
foreach (array_merge($socialLinks, $otherLinks) as $link) {
    $networkUrl = trim((string) ($link['url'] ?? ''));
    if ($networkUrl === '' || isset($seenUrls[$networkUrl])) {
        continue;
    }

    $networkLinks[] = [
        'label' => (string) ($link['label'] ?? $networkUrl),
        'url' => $networkUrl,
    ];
    $seenUrls[$networkUrl] = true;
}
?>

<section class='section page-shell'>
    <div class='container intro-grid'>
        <aside class='profile-panel about-profile-card'>
            <div class='profile-header'>
                <div class='profile-avatar'>
                    <?php if ($profileImage !== ''): ?>
                        <img src='<?= e($profileImage) ?>' alt='<?= e($displayName) ?>'>
                    <?php else: ?>
                        <div class='profile-avatar-fallback profile-avatar-fallback-circle'><?= e($initials) ?></div>
                    <?php endif; ?>
                    <div class='status-indicator'></div>
                </div>
                <h3><?= e($displayName) ?></h3>
                <span class='role'><?= e($profile['title'] ?? 'Developpeur fullstack') ?></span>
            </div>

            <?php if ($aboutStats !== []): ?>
                <div class='profile-stats'>
                    <?php foreach ($aboutStats as $item): ?>
                        <div class='stat-item'>
                            <h4><?= e($item['value']) ?></h4>
                            <p><?= e($item['label']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class='profile-actions'>
                <?php if ($cvUrl !== ''): ?><a class='btn-primary' href='<?= e($cvUrl) ?>' target='_blank' rel='noreferrer'>Voir le CV</a><?php endif; ?>
                <a class='btn-secondary' href='<?= url('/contact') ?>'>Me contacter</a>
            </div>

            <?php if ($socialLinks !== []): ?>
                <div class='social-connect'>
                    <?php foreach ($socialLinks as $link): ?>
                        <?php $iconClass = social_platform_icon((string) $link['label'], (string) $link['url']); ?>
                        <a href='<?= e($link['url']) ?>' target='_blank' rel='noreferrer' title='<?= e($link['label']) ?>' aria-label='<?= e($link['label']) ?>'><i class='<?= e($iconClass) ?>' aria-hidden='true'></i></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <div class='content-panel content-wrapper'>
            <div class='bio-section'>
                <div class='section-tag'>A propos</div>
                <h2>Une vue d'ensemble du profil, du parcours et de la facon de travailler.</h2>
                <p class='lead'><?= e($profile['bio'] ?? 'Ce portfolio presente un profil, des projets et un espace administrable.') ?></p>
            </div>

            <?php if ($detailItems !== []): ?>
                <div class='details-grid'>
                    <?php foreach ($detailItems as $item): ?>
                        <div class='detail-item'>
                            <div class='detail-content'>
                                <span><?= e($item['label']) ?></span>
                                <strong>
                                    <?php if (!empty($item['href'])): ?>
                                        <a href='<?= e((string) $item['href']) ?>' <?= str_starts_with((string) $item['href'], 'http') ? "target='_blank' rel='noreferrer'" : '' ?>><?= e($item['value']) ?></a>
                                    <?php else: ?>
                                        <?= e($item['value']) ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($skills !== []): ?>
                <div class='skills-showcase'>
                    <div class='section-tag'>Competences</div>
                    <h3>Maitrise technique</h3>
                    <div class='skills-list'>
                        <?php foreach ($skills as $skill): ?>
                            <?php $skillPercent = skill_level_percent($skill['niveau'] ?? 0); ?>
                            <div class='skill-item'>
                                <div class='skill-info'>
                                    <span class='skill-name'><?= e($skill['nom']) ?></span>
                                    <span class='skill-percent'><?= e((string) $skillPercent) ?>%</span>
                                </div>
                                <div class='progress'><div class='progress-bar' style='width:<?= e((string) $skillPercent) ?>%'></div></div>
                                <?php if (!empty($skill['description'])): ?><p class='meta'><?= e($skill['description']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($networkLinks !== []): ?>
                <div class='network-section'>
                    <div class='section-tag'>Reseaux</div>
                    <h3>Retrouve-moi aussi ici</h3>
                    <div class='network-grid'>
                        <?php foreach ($networkLinks as $link): ?>
                            <?php $iconClass = social_platform_icon((string) $link['label'], (string) $link['url']); ?>
                            <a class='network-card' href='<?= e($link['url']) ?>' target='_blank' rel='noreferrer' title='<?= e($link['label']) ?>' aria-label='<?= e($link['label']) ?>'>
                                <span class='network-icon'><i class='<?= e($iconClass) ?>' aria-hidden='true'></i></span>
                                <span class='network-label'><?= e($link['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($video = presentation_video_data($profile['presentation_video_url'] ?? null)): ?>
<section class='section'>
    <div class='container'>
        <div class='card video-feature'>
            <div class='section-head compact-head'>
                <div>
                    <div class='kicker'>Presentation</div>
                    <h2>Video de presentation</h2>
                </div>
            </div>
            <div class='video-shell'>
                <?php if ($video['type'] === 'embed'): ?>
                    <iframe src='<?= e($video['src']) ?>' title='Video de presentation' loading='lazy' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share' allowfullscreen referrerpolicy='strict-origin-when-cross-origin'></iframe>
                <?php else: ?>
                    <video controls preload='metadata' src='<?= e($video['src']) ?>'></video>
                <?php endif; ?>
            </div>
            <div class='button-row'>
                <a class='btn ghost' href='<?= e($video['url']) ?>' target='_blank' rel='noreferrer'>Ouvrir la video</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>