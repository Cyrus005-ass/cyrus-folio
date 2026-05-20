<?php
use App\Services\ThemeService;

$siteName = (string) app_config('name', env('APP_NAME', 'Cyrus-y ASSOGBA'));
$locale = (string) app_config('locale', 'fr_FR');
$htmlLang = strtolower(strtok(str_replace('-', '_', $locale), '_') ?: 'fr');
$pageTitle = trim((string) ($title ?? ''));
$fullTitle = $pageTitle !== '' ? $pageTitle . ' | ' . $siteName : $siteName;
$descriptionSource = $metaDescription ?? ($profile['bio'] ?? app_config('description', $siteName));
$metaDescription = excerpt((string) $descriptionSource, 170);
$metaKeywords = trim((string) ($metaKeywords ?? app_config('keywords', '')));
$metaAuthor = trim((string) ($metaAuthor ?? ($profile['full_name'] ?? app_config('author', $siteName))));
$metaRobots = trim((string) ($metaRobots ?? app_config('robots', 'noindex,nofollow')));
$canonical = trim((string) ($canonical ?? url(current_uri())));
$metaType = trim((string) ($metaType ?? 'website'));
$metaImage = absolute_url($metaImage ?? ($profile['avatar_url'] ?? app_config('og_image', ''))) ?? '';
$twitterHandle = trim((string) app_config('twitter_handle', ''));
$activeTheme = ThemeService::activeTheme();
$themeColor = trim((string) ($activeTheme['primary_color'] ?? app_config('theme_color', '#ff4d4f')));
$twitterCard = $metaImage !== '' ? 'summary_large_image' : 'summary';
?>
<!doctype html>
<html lang='<?= e($htmlLang) ?>'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title><?= e($fullTitle) ?></title>
    <meta name='description' content='<?= e($metaDescription) ?>'>
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&family=Raleway:wght@500;600;700;800;900&family=Roboto:wght@400;500;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>'>
    <?php if ($metaKeywords !== ''): ?><meta name='keywords' content='<?= e($metaKeywords) ?>'><?php endif; ?>
    <meta name='author' content='<?= e($metaAuthor) ?>'>
    <meta name='robots' content='<?= e($metaRobots) ?>'>
    <meta name='referrer' content='strict-origin-when-cross-origin'>
    <meta name='theme-color' content='<?= e($themeColor) ?>'>
    <link rel='canonical' href='<?= e($canonical) ?>'>

    <meta property='og:locale' content='<?= e($locale) ?>'>
    <meta property='og:type' content='<?= e($metaType) ?>'>
    <meta property='og:site_name' content='<?= e($siteName) ?>'>
    <meta property='og:title' content='<?= e($fullTitle) ?>'>
    <meta property='og:description' content='<?= e($metaDescription) ?>'>
    <meta property='og:url' content='<?= e($canonical) ?>'>
    <?php if ($metaImage !== ''): ?><meta property='og:image' content='<?= e($metaImage) ?>'><?php endif; ?>

    <meta name='twitter:card' content='<?= e($twitterCard) ?>'>
    <meta name='twitter:title' content='<?= e($fullTitle) ?>'>
    <meta name='twitter:description' content='<?= e($metaDescription) ?>'>
    <?php if ($twitterHandle !== ''): ?><meta name='twitter:creator' content='<?= e($twitterHandle) ?>'><?php endif; ?>
    <?php if ($metaImage !== ''): ?><meta name='twitter:image' content='<?= e($metaImage) ?>'><?php endif; ?>

    <style><?= ThemeService::cssVariables() ?></style>
    <link rel='stylesheet' href='<?= asset('css/main.css') ?>'>
    <link rel='stylesheet' href='<?= asset('css/theme.css') ?>'>
    <?php if (ThemeService::animationsEnabled()): ?><link rel='stylesheet' href='<?= asset('css/animations.css') ?>'><?php endif; ?>
    <script>window.APP_URL = <?= json_encode(app_url()) ?>;</script>
</head>
<body class='<?= e(current_uri() === '/' ? 'home-page' : 'inner-page') ?>'>
<?php require RESOURCE_PATH . '/components/navbar.php'; ?>
<main>
    <?php if ($msg = flash('success')): ?><div class='container'><div class='alert success'><?= e($msg) ?></div></div><?php endif; ?>
    <?php if ($msg = flash('warning')): ?><div class='container'><div class='alert warning'><?= e($msg) ?></div></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class='container'><div class='alert error'><?= e($msg) ?></div></div><?php endif; ?>
    <?= $content ?>
</main>
<?php require RESOURCE_PATH . '/components/footer.php'; ?>
<?php require RESOURCE_PATH . '/components/chatbot-widget.php'; ?>
<script src='<?= asset('js/main.js') ?>'></script>
<script src='<?= asset('js/chatbot.js') ?>'></script>
</body>
</html>
