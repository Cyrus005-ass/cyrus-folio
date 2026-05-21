<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Catalogue</div>
            <h1>Tous les projets</h1>
            <p class='lead'>Explore les realisations, stacks techniques et liens de demonstration dans une grille visuelle plus proche de la direction Craftivo.</p>
        </div>

        <?php if (!empty($technology)): ?>
            <div class='alert success'>Filtre actif : <?= e($technology) ?></div>
        <?php endif; ?>

        <?php if (!empty($projects)): ?>
            <div class='portfolio-grid listing-grid'>
                <?php foreach ($projects as $project): ?>
                    <?php $projectImage = absolute_url($project['image_url'] ?? null) ?? ''; ?>
                    <article class='portfolio-card'>
                        <div class='portfolio-media'>
                            <?php if ($projectImage !== ''): ?>
                                <img src='<?= e($projectImage) ?>' alt='<?= e($project['titre']) ?>' loading='lazy' decoding='async' fetchpriority='low'>
                            <?php else: ?>
                                <div class='project-cover'><?= e($project['titre']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class='portfolio-copy'>
                            <div class='split-line'>
                                <h3><?= e($project['titre']) ?></h3>
                                <span class='meta'><?= e($project['statut'] ?? 'publie') ?></span>
                            </div>

                            <p class='meta'><?= e($project['description'] ?? '') ?></p>

                        <?php if (!empty($project['technologies'])): ?>
                            <div class='tags'>
                                <?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string) $project['technologies']))), 0, 5) as $tech): ?>
                                    <span class='tag'><?= e($tech) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                            <div class='button-row'>
                                <a class='btn ghost' href='<?= url('/projects/' . ($project['slug'] ?? '')) ?>'>Details</a>
                                <?php if (!empty($project['github_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($project['github_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                                <?php if (!empty($project['demo_url'])): ?><a class='btn' href='<?= e(absolute_url($project['demo_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>Demo</a><?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucun projet disponible.</div>
        <?php endif; ?>
    </div>
</section>
