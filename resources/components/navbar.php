<?php
$brandName = trim((string) (($profile['full_name'] ?? '') ?: env('APP_NAME', 'Cyrus-y ASSOGBA')));
$brandParts = preg_split('/\s+/', $brandName) ?: [$brandName];
$brandShort = 'C-Y';
$logoVideoUrl = is_file(public_path('assets/uploads/C-y.mp4')) ? asset('uploads/C-y.mp4') : '';
$brandVideoEnabled = (bool) env('APP_BRAND_VIDEO', false);
$useLogoVideo = $brandVideoEnabled && $logoVideoUrl !== '' && !save_data_enabled();
?>

<header class='navbar'>
    <div class='container nav-inner'>
        <a class='brand brand-text' href='<?= url('/') ?>'>
            <?php if ($useLogoVideo): ?>
                <span class='brand-emblem' aria-hidden='true'>
                    <video class='brand-logo-video' autoplay muted loop playsinline preload='metadata'>
                        <source src='<?= e($logoVideoUrl) ?>' type='video/mp4'>
                    </video>
                </span>
            <?php else: ?>
                <span class='brand-mark' aria-hidden='true'>C-Y</span>
            <?php endif; ?>
            <span class='sitename'><?= e($brandShort !== '' ? $brandShort : $brandName) ?></span>
        </a>

        <button class='nav-toggle' type='button' aria-expanded='false' aria-controls='site-nav' aria-label='Ouvrir la navigation'>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav id='site-nav' class='nav-links'>
            <a class='<?= active_class('/') ?>' href='<?= url('/') ?>'>Accueil</a>
            <a class='<?= active_class('/about') ?>' href='<?= url('/about') ?>'>A propos</a>
            <a class='<?= active_class('/projects') ?>' href='<?= url('/projects') ?>'>Projets</a>
            <a class='<?= active_class('/skills') ?>' href='<?= url('/skills') ?>'>Competences</a>
            <a class='<?= active_class('/certifications') ?>' href='<?= url('/certifications') ?>'>Certifications</a>
            <a class='<?= active_class('/blog') ?>' href='<?= url('/blog') ?>'>Blog</a>
            <a class='btn nav-cta' href='<?= url('/contact') ?>'>Demarrer</a>
        </nav>
    </div>
</header>
