<section class='section page-shell project-detail-page'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Projet</div>
            <h1><?= e($project['titre'] ?? 'Projet') ?></h1>
            <p class='lead'><?= e($project['description'] ?? '') ?></p>

            <div class='button-row'>
                <a class='btn ghost' href='<?= url('/projects') ?>'>Retour aux projets</a>
                <?php if (!empty($project['github_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($project['github_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                <?php if (!empty($project['demo_url'])): ?><a class='btn' href='<?= e(absolute_url($project['demo_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>Voir la démo</a><?php endif; ?>
            </div>
        </div>

        <div class='card detail-shell'>

            <?php if (!empty($project['technologies'])): ?>
                <div class='tags'>
                    <?php foreach (array_filter(array_map('trim', explode(',', (string) $project['technologies']))) as $tech): ?>
                        <span class='tag'><?= e($tech) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $galleryImages = decode_json_array($project['gallery_images'] ?? null); ?>
            <?php if (!empty($project['image_url'])): ?><img class='detail-hero-media' src='<?= e(absolute_url($project['image_url'] ?? null) ?? '') ?>' alt='<?= e($project['titre'] ?? 'Projet') ?>' loading='eager' decoding='async' fetchpriority='high'><?php endif; ?>
            <?php if (!empty($galleryImages)): ?>
                <div class='grid grid-3'>
                    <?php foreach ($galleryImages as $image): ?>
                        <img class='detail-gallery-media' src='<?= e(absolute_url($image) ?? '') ?>' alt='Image du projet' loading='lazy' decoding='async' fetchpriority='low'>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($project['contenu'])): ?>
                <div class='rich-content'><?= nl2br(e($project['contenu'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($collaborations)): ?>
                <div class='section-head compact-head' style='margin-top:24px;'>
                    <div>
                        <div class='kicker'>Collaboration</div>
                        <h2>Personnes impliquées sur ce projet</h2>
                    </div>
                </div>
                <div class='grid grid-2'>
                    <?php foreach ($collaborations as $collaboration): ?>
                        <article class='mini-card'>
                            <div class='split-line'><strong><?= e($collaboration['nom_membre']) ?></strong><span class='meta'><?= e($collaboration['role'] ?? '') ?></span></div>
                            <?php if (!empty($collaboration['contribution'])): ?><p class='meta'><?= e($collaboration['contribution']) ?></p><?php endif; ?>
                            <?php if (!empty($collaboration['email']) || !empty($collaboration['portfolio_url']) || !empty($collaboration['github_url']) || !empty($collaboration['linkedin_url'])): ?>
                                <div class='button-row'>
                                    <?php if (!empty($collaboration['email'])): ?><a class='btn ghost' href='mailto:<?= e($collaboration['email']) ?>'>Email</a><?php endif; ?>
                                    <?php if (!empty($collaboration['portfolio_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($collaboration['portfolio_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>Portfolio</a><?php endif; ?>
                                    <?php if (!empty($collaboration['github_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($collaboration['github_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                                    <?php if (!empty($collaboration['linkedin_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($collaboration['linkedin_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>LinkedIn</a><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
