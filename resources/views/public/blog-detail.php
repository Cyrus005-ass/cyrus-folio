<section class='section page-shell'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Article</div>
            <h1><?= e($post['titre'] ?? 'Article') ?></h1>
            <div class='split-line'>
                <span class='tag'><?= e($post['category'] ?? 'autre') ?></span>
                <span class='meta'><?= (int) ($post['view_count'] ?? 0) ?> vues</span>
            </div>
            <?php if (!empty($post['published_at'])): ?><p class='meta'>Publie le <?= e($post['published_at']) ?></p><?php endif; ?>
        </div>

        <article class='card detail-shell'>
            <?php if (!empty($post['image_url'])): ?><img src='<?= e(absolute_url($post['image_url'] ?? null) ?? '') ?>' alt='<?= e($post['titre'] ?? 'Article') ?>' loading='eager' decoding='async' fetchpriority='high' style='max-height:360px;width:100%;object-fit:cover;border-radius:20px;'><?php endif; ?>
            <?php if (!empty($post['tags'])): ?>
                <div class='tags'>
                    <?php foreach (array_filter(array_map('trim', explode(',', (string) $post['tags']))) as $tag): ?>
                        <span class='tag'><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class='rich-content'><?= sanitize_rich_text($post['contenu'] ?? '') ?? '' ?></div>
            <div class='button-row'>
                <a class='btn ghost' href='<?= url('/blog') ?>'>Retour au blog</a>
            </div>
        </article>
    </div>
</section>
