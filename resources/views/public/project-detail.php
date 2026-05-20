<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Projet</div>
            <h1><?= e($project['titre'] ?? 'Projet') ?></h1>
            <p class='lead'><?= e($project['description'] ?? '') ?></p>

            <div class='button-row'>
                <a class='btn ghost' href='<?= url('/projects') ?>'>Retour</a>
                <?php if (!empty($project['github_url'])): ?><a class='btn ghost' href='<?= e($project['github_url']) ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                <?php if (!empty($project['demo_url'])): ?><a class='btn' href='<?= e($project['demo_url']) ?>' target='_blank' rel='noreferrer'>Voir la demo</a><?php endif; ?>
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
            <?php if (!empty($project['image_url'])): ?><img src='<?= e(url('/' . ltrim((string) $project['image_url'], '/'))) ?>' alt='<?= e($project['titre'] ?? 'Projet') ?>' style='max-height:360px;width:100%;object-fit:cover;border-radius:20px;'><?php endif; ?>
            <?php if (!empty($galleryImages)): ?>
                <div class='grid grid-3'>
                    <?php foreach ($galleryImages as $image): ?>
                        <img src='<?= e(url('/' . ltrim($image, '/'))) ?>' alt='Image du projet' style='height:180px;width:100%;object-fit:cover;border-radius:18px;'>
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
                        <h2>Personnes mentionnees sur ce projet</h2>
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
                                    <?php if (!empty($collaboration['portfolio_url'])): ?><a class='btn ghost' href='<?= e($collaboration['portfolio_url']) ?>' target='_blank' rel='noreferrer'>Portfolio</a><?php endif; ?>
                                    <?php if (!empty($collaboration['github_url'])): ?><a class='btn ghost' href='<?= e($collaboration['github_url']) ?>' target='_blank' rel='noreferrer'>GitHub</a><?php endif; ?>
                                    <?php if (!empty($collaboration['linkedin_url'])): ?><a class='btn ghost' href='<?= e($collaboration['linkedin_url']) ?>' target='_blank' rel='noreferrer'>LinkedIn</a><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
