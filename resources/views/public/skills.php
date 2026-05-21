<section class='section page-shell skills-page'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Expertise</div>
            <h1>Compétences</h1>
            <p class='lead'>Une lecture plus claire des stacks et expertises, avec une présentation plus nette et plus visible.</p>
        </div>
        <?php if (!empty($groups)): ?>
            <div class='grid grid-2 skills-grid'>
                <?php foreach ($groups as $category => $items): ?>
                    <div class='card skills-group-card'>
                        <h2><?= e(skill_category_label((string) $category)) ?></h2>
                        <div class='stack-list skills-stack'>
                            <?php foreach ($items as $skill): ?>
                                <div class='skill-item skill-meter'>
                                    <div class='split-line'><strong><?= e($skill['nom']) ?></strong><span class='meta'><?= e(skill_level_label($skill['niveau'] ?? '')) ?></span></div>
                                    <div class='progress'><span style='width:<?= skill_level_percent($skill['niveau'] ?? 0) ?>%'></span></div>
                                    <?php if (!empty($skill['description'])): ?><p class='meta'><?= e($skill['description']) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune compétence disponible.</div>
        <?php endif; ?>
    </div>
</section>
