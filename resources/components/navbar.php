<?php
$brandName = trim((string) (($profile['full_name'] ?? '') ?: env('APP_NAME', 'Cyrus-y ASSOGBA')));
$logoVideoUrl = is_file(public_path('assets/uploads/C-y.mp4')) ? asset('uploads/C-y.mp4') : '';
$brandVideoEnabled = (bool) env('APP_BRAND_VIDEO', false);
$useLogoVideo = $brandVideoEnabled && $logoVideoUrl !== '' && !save_data_enabled();
$logoImageUrl = absolute_url((string) app_config('icons.favicon_svg', 'assets/icons/favicon.svg')) ?? asset('icons/favicon.svg');
$useLogoImage = !$useLogoVideo && $logoImageUrl !== '';
?>

<header class='navbar'>
    <div class='container nav-inner'>
        <a class='brand brand-text' href='<?= path_url('/') ?>' aria-label='<?= e($brandName) ?>' title='<?= e($brandName) ?>'>
            <?php if ($useLogoVideo): ?>
                <span class='brand-emblem' aria-hidden='true'>
                    <video class='brand-logo-video' autoplay muted loop playsinline preload='metadata'>
                        <source src='<?= e($logoVideoUrl) ?>' type='video/mp4'>
                    </video>
                </span>
            <?php elseif ($useLogoImage): ?>
                <span class='brand-emblem brand-emblem-static' aria-hidden='true'>
                    <img class='brand-logo-image' src='<?= e($logoImageUrl) ?>' alt='' loading='eager' decoding='async'>
                </span>
            <?php else: ?>
                <span class='brand-mark' aria-hidden='true'></span>
            <?php endif; ?>
            <span class='sr-only'><?= e($brandName) ?></span>
        </a>

        <button class='nav-toggle' type='button' aria-expanded='false' aria-controls='site-nav' aria-label='Ouvrir la navigation'>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav id='site-nav' class='nav-links'>
            <a class='<?= active_class('/') ?>' href='<?= path_url('/') ?>'>Accueil</a>
            <a class='<?= active_class('/about') ?>' href='<?= path_url('/about') ?>'>À propos</a>
            <a class='<?= active_class('/projects') ?>' href='<?= path_url('/projects') ?>'>Projets</a>
            <a class='<?= active_class('/skills') ?>' href='<?= path_url('/skills') ?>'>Compétences</a>
            <a class='<?= active_class('/certifications') ?>' href='<?= path_url('/certifications') ?>'>Certifications</a>
            <a class='<?= active_class('/blog') ?>' href='<?= path_url('/blog') ?>'>Blog</a>
            <a class='btn nav-cta' href='<?= path_url('/contact') ?>'>Démarrer</a>
        </nav>
    </div>
</header>
