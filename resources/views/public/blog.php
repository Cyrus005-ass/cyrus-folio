<section class='section page-shell blog-page'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Publication</div>
            <h1>Blog</h1>
            <p class='lead'>Articles, notes et retours d'expérience dans une grille plus compacte et plus facile à parcourir.</p>
        </div>
        <?php if (!empty($posts)): ?>
            <div class='blog-grid'>
                <?php foreach ($posts as $post): ?>
                    <?php $publishedAt = format_french_date($post['published_at'] ?? null); ?>
                    <article class='card blog-card'>
                        <?php if (!empty($post['image_url'])): ?><img class='blog-card-media' src='<?= e(absolute_url($post['image_url'] ?? null) ?? '') ?>' alt='<?= e($post['titre']) ?>' loading='lazy' decoding='async' fetchpriority='low'><?php endif; ?>
                        <div class='blog-card-body'>
                            <div class='tag'><?= e($post['category'] ?? 'autre') ?></div>
                            <h2><?= e($post['titre']) ?></h2>
                            <p class='meta'><?= e(excerpt($post['extrait'] ?? strip_tags((string) ($post['contenu'] ?? '')), 148)) ?></p>
                            <div class='blog-card-footer'>
                                <?php if ($publishedAt !== ''): ?><span class='blog-card-date'><?= e($publishedAt) ?></span><?php endif; ?>
                                <a class='btn ghost' href='<?= url('/blog/' . ($post['slug'] ?? '')) ?>'>Lire</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucun article publié.</div>
        <?php endif; ?>
    </div>
</section>
