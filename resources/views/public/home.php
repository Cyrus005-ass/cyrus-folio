<?php
$displayName = trim((string) (($profile['full_name'] ?? '') ?: 'Cyrus-y ASSOGBA'));
$titleText = trim((string) (($profile['title'] ?? '') ?: 'Fullstack Developer'));
$bioText = trim((string) (($profile['bio'] ?? '') ?: 'Je developpe des experiences web premium, administrables et pensees pour evoluer proprement.'));
$profileImage = absolute_url($profile['avatar_url'] ?? null) ?? '';
$cvUrl = absolute_url($profile['cv_url'] ?? null) ?? '';
$websiteUrl = absolute_url($profile['website_url'] ?? null) ?? '';
$websiteLabel = $websiteUrl !== '' ? (parse_url($websiteUrl, PHP_URL_HOST) ?: $websiteUrl) : '';
$socialLinks = profile_social_links($profile ?? null);
$featuredItems = array_slice($featuredProjects ?? [], 0, 3);
$homeSkills = is_array($homeSkills ?? null) ? $homeSkills : [];
$homeSkillGroups = is_array($homeSkillGroups ?? null) ? $homeSkillGroups : [];
$homeStats = is_array($homeStats ?? null) ? $homeStats : [];
$typedItems = array_values(array_unique(array_filter([
    $titleText,
    'Developpeur backend',
    'Integrateur frontend',
    'Createur de solutions web',
], static fn ($item) => trim((string) $item) !== '')));
$typedItemsAttr = implode('|', $typedItems);

$overviewDetails = [];
if (!empty($profile['location'])) {
    $overviewDetails[] = ['icon' => 'bi bi-geo-alt', 'label' => 'Base', 'value' => (string) $profile['location'], 'href' => null];
}
if (!empty($profile['availability'])) {
    $overviewDetails[] = ['icon' => 'bi bi-briefcase', 'label' => 'Disponibilite', 'value' => ucfirst(str_replace('_', ' ', (string) $profile['availability'])), 'href' => null];
}
if (!empty($profile['email'])) {
    $overviewDetails[] = ['icon' => 'bi bi-envelope', 'label' => 'Email', 'value' => (string) $profile['email'], 'href' => 'mailto:' . (string) $profile['email']];
}
if ($websiteUrl !== '') {
    $overviewDetails[] = ['icon' => 'bi bi-globe2', 'label' => 'Site web', 'value' => (string) $websiteLabel, 'href' => $websiteUrl];
}
$overviewDetails = array_slice($overviewDetails, 0, 4);

$serviceMeta = [
    'Frameworks' => ['icon' => 'bi bi-window-stack', 'description' => 'Des interfaces, applications et experiences web modernes, fluides et evolutives.'],
    'Langages' => ['icon' => 'bi bi-code-slash', 'description' => 'Des fondations techniques propres pour construire vite, bien et durablement.'],
    'Outils' => ['icon' => 'bi bi-gear-wide-connected', 'description' => 'Un environnement de production structure pour livrer plus proprement et plus sereinement.'],
    'Securite' => ['icon' => 'bi bi-shield-check', 'description' => 'Une attention concrete a la fiabilite, a la protection et a la qualite globale du produit.'],
    'Autre' => ['icon' => 'bi bi-layers', 'description' => 'Des competences complementaires utiles pour faire avancer un produit dans son ensemble.'],
];

$serviceCards = [];
foreach ($homeSkillGroups as $category => $items) {
    $meta = $serviceMeta[(string) $category] ?? ['icon' => 'bi bi-stars', 'description' => 'Une expertise adaptable aux besoins du projet et a son niveau de maturite.'];
    $serviceCards[] = [
        'title' => (string) $category,
        'description' => $meta['description'],
        'icon' => $meta['icon'],
        'count' => count($items),
        'tags' => array_slice(array_values(array_filter(array_map(static fn ($item) => trim((string) ($item['nom'] ?? '')), $items))), 0, 3),
    ];
}

if ($serviceCards === []) {
    $serviceCards = [
        ['title' => 'Design & interfaces', 'description' => 'Des ecrans sobres, clairs et premium qui servent vraiment le contenu et la conversion.', 'icon' => 'bi bi-grid-1x2', 'count' => 3, 'tags' => ['UI premium', 'Responsive', 'Direction visuelle']],
        ['title' => 'Developpement web', 'description' => 'Des experiences web rapides, administrables et faciles a faire evoluer proprement.', 'icon' => 'bi bi-code-square', 'count' => 3, 'tags' => ['Frontend', 'Backend', 'Architecture']],
        ['title' => 'Livraison produit', 'description' => 'Une execution structuree avec de bons choix techniques, du confort admin et un rendu soigne.', 'icon' => 'bi bi-rocket-takeoff', 'count' => 3, 'tags' => ['Qualite', 'Maintenance', 'Performance']],
    ];
}
?>

<section class='hero'>
    <?php if ($profileImage !== ''): ?><img class='hero-bg-media' src='<?= e($profileImage) ?>' alt='<?= e($displayName) ?>'><?php endif; ?>
    <div class='hero-backdrop'></div>
    <div class='container hero-stack'>
        <h1>Salut, je suis <?= e($displayName) ?></h1>
        <p class='hero-subtitle'>
            Je suis
            <span class='typed-text' data-typed-items='<?= e($typedItemsAttr) ?>'><?= e($typedItems[0] ?? $titleText) ?></span>
            <span class='typed-cursor' aria-hidden='true'>|</span>
        </p>
        <p class='hero-summary'>Je developpe des experiences web premium, administrables et pensees pour evoluer proprement, avec un vrai soin du rendu et de la structure.</p>

        <?php if ($socialLinks !== []): ?>
            <div class='social-links'>
                <?php foreach ($socialLinks as $link): ?>
                    <?php $iconClass = social_platform_icon((string) $link['label'], (string) $link['url']); ?>
                    <a href='<?= e($link['url']) ?>' target='_blank' rel='noreferrer' title='<?= e($link['label']) ?>' aria-label='<?= e($link['label']) ?>'>
                        <i class='<?= e($iconClass) ?>' aria-hidden='true'></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class='section home-intro-section'>
    <div class='container home-intro-grid'>
        <article class='intro-story card' data-reveal>
            <div class='section-tag'>About</div>
            <h2>Un profil fullstack oriente produit, execution et rendu premium.</h2>
            <p class='lead'><?= e($bioText) ?></p>
            <p class='intro-copy'>Je travaille avec une approche claire : comprendre le besoin, poser une structure solide, puis livrer une experience visuelle sobre, moderne et simple a faire evoluer.</p>
            <div class='button-row'>
                <a class='btn' href='<?= url('/about') ?>'>Lire le profil complet</a>
                <a class='btn ghost' href='<?= url('/contact') ?>'>Parler de ton projet</a>
                <?php if ($cvUrl !== ''): ?><a class='btn ghost' href='<?= e($cvUrl) ?>' target='_blank' rel='noreferrer'>Voir le CV</a><?php endif; ?>
            </div>
        </article>

        <aside class='overview-panel card' data-reveal style='--reveal-delay: 120ms;'>
            <div class='overview-panel-head'>
                <div class='section-tag'>Resume</div>
                <h3>En bref</h3>
            </div>

            <?php if ($homeStats !== []): ?>
                <div class='overview-stat-grid'>
                    <?php foreach ($homeStats as $item): ?>
                        <div class='overview-stat'>
                            <strong><?= e($item['value']) ?></strong>
                            <span><?= e($item['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($overviewDetails !== []): ?>
                <div class='overview-detail-list'>
                    <?php foreach ($overviewDetails as $item): ?>
                        <div class='overview-detail'>
                            <i class='<?= e($item['icon']) ?>' aria-hidden='true'></i>
                            <div>
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
            <?php else: ?>
                <p class='meta'>Le profil detaille, les informations completes et la video de presentation restent centralises sur la page A propos.</p>
            <?php endif; ?>
        </aside>
    </div>
</section>

<section class='section home-services-section'>
    <div class='container'>
        <div class='section-head' data-reveal>
            <div>
                <div class='kicker'>Services</div>
                <h2>Une approche claire du design a la livraison</h2>
                <p class='lead'>L'accueil montre ici un apercu propre des expertises principales, sans remonter toute la page competences dans son integralite.</p>
            </div>
            <a class='btn ghost' href='<?= url('/skills') ?>'>Voir les competences</a>
        </div>

        <div class='service-preview-grid'>
            <?php foreach ($serviceCards as $index => $card): ?>
                <article class='service-preview-card' data-reveal style='--reveal-delay: <?= e((string) (($index + 1) * 100)) ?>ms;'>
                    <div class='service-preview-top'>
                        <span class='service-preview-icon'><i class='<?= e($card['icon']) ?>' aria-hidden='true'></i></span>
                        <span class='service-preview-count'><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    </div>
                    <span class='service-preview-meta'><?= e((string) $card['count']) ?> repere(s)</span>
                    <h3><?= e($card['title']) ?></h3>
                    <p><?= e($card['description']) ?></p>
                    <?php if (!empty($card['tags'])): ?>
                        <div class='service-preview-tags'>
                            <?php foreach ($card['tags'] as $tag): ?>
                                <span><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($homeSkills !== []): ?>
            <div class='stack-ribbon card' data-reveal style='--reveal-delay: 240ms;'>
                <div>
                    <div class='kicker'>Stack</div>
                    <h3>Technologies que j'utilise regulierement</h3>
                </div>
                <div class='skill-chip-grid'>
                    <?php foreach ($homeSkills as $skill): ?>
                        <?php $skillPercent = skill_level_percent($skill['niveau'] ?? 0); ?>
                        <span class='skill-chip'>
                            <strong><?= e($skill['nom']) ?></strong>
                            <small><?= e((string) $skillPercent) ?>%</small>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($featuredItems !== []): ?>
<section class='section home-portfolio-section'>
    <div class='container'>
        <div class='section-head' data-reveal>
            <div>
                <div class='kicker'>Portfolio</div>
                <h2>Une selection de projets a explorer</h2>
                <p class='lead'>Quelques realisations choisies pour offrir un apercu plus vivant du portfolio, sans transformer l'accueil en catalogue complet.</p>
            </div>
            <a class='btn ghost' href='<?= url('/projects') ?>'>Tous les projets</a>
        </div>

        <div class='portfolio-grid'>
            <?php foreach ($featuredItems as $index => $project): ?>
                <?php $projectImage = absolute_url($project['image_url'] ?? null) ?? ''; ?>
                <article class='portfolio-card' data-reveal style='--reveal-delay: <?= e((string) (($index + 1) * 110)) ?>ms;'>
                    <div class='portfolio-media'>
                        <?php if ($projectImage !== ''): ?>
                            <img src='<?= e($projectImage) ?>' alt='<?= e($project['titre']) ?>'>
                        <?php else: ?>
                            <div class='project-cover'><?= e($project['titre']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class='portfolio-copy'>
                        <div class='split-line'>
                            <span class='tag'><?= e($project['statut'] ?? 'publie') ?></span>
                            <?php if (!empty($project['demo_url'])): ?><span class='meta'>Demo dispo</span><?php endif; ?>
                        </div>

                        <h3><?= e($project['titre']) ?></h3>
                        <p class='meta'><?= e($project['description'] ?? 'Projet portfolio.') ?></p>

                        <?php if (!empty($project['technologies'])): ?>
                            <div class='tags'>
                                <?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string) $project['technologies']))), 0, 4) as $tech): ?>
                                    <span class='tag'><?= e($tech) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class='button-row'>
                            <a class='btn ghost' href='<?= url('/projects/' . ($project['slug'] ?? '')) ?>'>Details</a>
                            <?php if (!empty($project['github_url'])): ?><a class='btn ghost' href='<?= e($project['github_url']) ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                            <?php if (!empty($project['demo_url'])): ?><a class='btn' href='<?= e($project['demo_url']) ?>' target='_blank' rel='noreferrer'>Voir la demo</a><?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class='section'>
    <div class='container'>
        <div class='cta-banner' data-reveal>
            <div>
                <div class='kicker'>Contact</div>
                <h2>Tu veux voir tout le parcours, mes reseaux et la video de presentation ?</h2>
                <p>La page A propos regroupe la presentation complete du profil et la video. Pour parler d'une mission ou d'un projet, la page Contact reste la meilleure entree.</p>
            </div>
            <div class='button-row'>
                <a class='btn' href='<?= url('/about') ?>'>Voir A propos</a>
                <a class='btn ghost' href='<?= url('/contact') ?>'>Lancer une discussion</a>
                <a class='btn ghost' href='<?= url('/projects') ?>'>Explorer le portfolio</a>
            </div>
        </div>
    </div>
</section>