<?php $socialLinks = profile_social_links($profile ?? null); ?>
<?php $displayName = trim((string) (($profile['full_name'] ?? '') ?: env('APP_NAME', 'Cyrus-y ASSOGBA'))); ?>
<?php $profileTitle = trim((string) ($profile['title'] ?? '')); ?>
<?php $profileBio = excerpt((string) (($profile['bio'] ?? '') ?: 'Portfolio personnel et professionnel.'), 180); ?>
<?php $phoneHref = preg_replace('/\s+/', '', (string) ($profile['phone'] ?? '')) ?: ''; ?>

<footer class='footer'>
  <div class='container footer-grid'>
    <div class='footer-brand'>
      <span class='footer-overline'>Portfolio personnel</span>
      <h2><?= e($displayName) ?></h2>
      <?php if ($profileTitle !== ''): ?><p class='footer-role'><?= e($profileTitle) ?></p><?php endif; ?>
      <p><?= e($profileBio) ?></p>
      <?php if ($socialLinks !== []): ?>
        <div class='social-row compact'>
          <?php foreach ($socialLinks as $link): ?>
            <a class='social-pill icon-only' href='<?= e($link['url']) ?>' target='_blank' rel='noreferrer' title='<?= e($link['label']) ?>' aria-label='<?= e($link['label']) ?>'>
              <span class='social-dot'><i class='<?= e(social_platform_icon((string) $link['label'], (string) $link['url'])) ?>' aria-hidden='true'></i></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class='footer-column'>
      <strong>Navigation</strong>
      <div class='footer-links-list'>
        <a href='<?= url('/') ?>'>Accueil</a>
        <a href='<?= url('/about') ?>'>A propos</a>
        <a href='<?= url('/projects') ?>'>Projets</a>
        <a href='<?= url('/skills') ?>'>Competences</a>
        <a href='<?= url('/blog') ?>'>Blog</a>
      </div>
    </div>

    <div class='footer-column'>
      <strong>Contact</strong>
      <div class='footer-links-list'>
        <?php if (!empty($profile['email'])): ?><a href='mailto:<?= e($profile['email']) ?>'><?= e($profile['email']) ?></a><?php endif; ?>
        <?php if ($phoneHref !== ''): ?><a href='tel:<?= e($phoneHref) ?>'><?= e($profile['phone']) ?></a><?php endif; ?>
        <?php if (!empty($profile['location'])): ?><span><?= e($profile['location']) ?></span><?php endif; ?>
        <?php if (!empty($profile['website_url'])): ?><a href='<?= e($profile['website_url']) ?>' target='_blank' rel='noreferrer'>Site web</a><?php endif; ?>
        <a href='<?= url('/contact') ?>'>Discuter d'un projet</a>
      </div>
    </div>
  </div>

  <div class='container footer-bar'>
    <p><?= date('Y') ?> <?= e($displayName) ?>. Tous droits reserves.</p>
    <p>Portfolio personnel, administrable et evolutif.</p>
  </div>
</footer>