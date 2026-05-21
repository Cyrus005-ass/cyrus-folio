<section class='section page-shell blog-detail-page'>
    <div class='container'>
        <div class='page-hero card'>
            <div class='kicker'>Article</div>
            <h1><?= e($post['titre'] ?? 'Article') ?></h1>
            <div class='split-line'>
                <span class='tag'><?= e($post['category'] ?? 'autre') ?></span>
                <?php if (!empty($post['published_at'])): ?><span class='meta'>Publié le <?= e(format_french_date($post['published_at'], true)) ?></span><?php endif; ?>
            </div>
        </div>

        <article class='card detail-shell'>
            <?php if (!empty($post['image_url'])): ?><img class='detail-hero-media' src='<?= e(absolute_url($post['image_url'] ?? null) ?? '') ?>' alt='<?= e($post['titre'] ?? 'Article') ?>' loading='eager' decoding='async' fetchpriority='high'><?php endif; ?>
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
