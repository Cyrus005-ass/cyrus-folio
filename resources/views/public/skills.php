<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Expertise</div>
            <h1>Competences</h1>
            <p class='lead'>Une lecture plus claire des stacks et expertises, avec une presentation editoriale inspiree de Craftivo.</p>
        </div>
        <?php if (!empty($groups)): ?>
            <div class='grid grid-2'>
                <?php foreach ($groups as $category => $items): ?>
                    <div class='card'>
                        <h2><?= e(ucfirst((string) $category)) ?></h2>
                        <div class='stack-list'>
                            <?php foreach ($items as $skill): ?>
                                <div class='skill-item'>
                                    <div class='split-line'><strong><?= e($skill['nom']) ?></strong><span class='meta'><?= e($skill['niveau'] ?? '') ?></span></div>
                                    <div class='progress'><span style='width:<?= skill_level_percent($skill['niveau'] ?? 0) ?>%'></span></div>
                                    <?php if (!empty($skill['description'])): ?><p class='meta'><?= e($skill['description']) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucune competence disponible.</div>
        <?php endif; ?>
    </div>
</section>
