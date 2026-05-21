<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Publication</div>
            <h1>Blog</h1>
            <p class='lead'>Articles, notes et retours d'experience dans une grille plus magazine.</p>
        </div>
        <?php if (!empty($posts)): ?>
            <div class='grid grid-2'>
                <?php foreach ($posts as $post): ?>
                    <article class='card'>
                        <?php if (!empty($post['image_url'])): ?><img src='<?= e(absolute_url($post['image_url'] ?? null) ?? '') ?>' alt='<?= e($post['titre']) ?>' loading='lazy' decoding='async' fetchpriority='low' style='height:220px;width:100%;object-fit:cover;border-radius:18px;margin-bottom:16px;'><?php endif; ?>
                        <div class='tag'><?= e($post['category'] ?? 'autre') ?></div>
                        <h2><?= e($post['titre']) ?></h2>
                        <p class='meta'><?= e(excerpt($post['extrait'] ?? strip_tags((string) ($post['contenu'] ?? '')), 160)) ?></p>
                        <div class='button-row'>
                            <a class='btn ghost' href='<?= url('/blog/' . ($post['slug'] ?? '')) ?>'>Lire</a>
                            <?php if (!empty($post['published_at'])): ?><span class='meta'><?= e($post['published_at']) ?></span><?php endif; ?>
                            <span class='meta'><?= (int) ($post['view_count'] ?? 0) ?> vues</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class='empty'>Aucun article publie.</div>
        <?php endif; ?>
    </div>
</section>
