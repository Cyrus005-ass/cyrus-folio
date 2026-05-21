<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Credibilite</div>
            <h1>Certifications</h1>
            <p class='lead'>Badges, preuves de niveau et liens de verification presentes dans une mise en page plus premium.</p>
        </div>
        <?php if (!empty($certifications)): ?>
            <div class='grid grid-2'>
                <?php foreach ($certifications as $certification): ?>
                    <article class='card cert-row'>
                        <div class='cert-badge'><?= e(substr((string) ($certification['titre'] ?? 'C'), 0, 2)) ?></div>
                        <div>
                            <h3><?= e($certification['titre']) ?></h3>
                            <p class='meta'><?= e($certification['organisme'] ?? '') ?></p>
                            <p class='meta'>Obtenue le <?= e($certification['date_obtention'] ?? '') ?></p>
                            <?php if (!empty($certification['badge_url']) || !empty($certification['lien_verification'])): ?>
                                <div class='button-row'>
                                    <?php if (!empty($certification['badge_url'])): ?><a class='btn ghost' href='<?= e(absolute_url($certification['badge_url'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>Voir le badge</a><?php endif; ?>
                                    <?php if (!empty($certification['lien_verification'])): ?><a class='btn ghost' href='<?= e(absolute_url($certification['lien_verification'] ?? null) ?? '') ?>' target='_blank' rel='noreferrer'>Verifier la certification</a><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune certification disponible.</div>
        <?php endif; ?>
    </div>
</section>
