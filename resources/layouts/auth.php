<?php
use App\Services\ThemeService;

$siteName = (string) env('APP_NAME', 'Cyrus-y ASSOGBA');
$activeTheme = ThemeService::activeTheme();
$themeColor = trim((string) ($activeTheme['primary_color'] ?? '#ff4d4f'));
?>
<!doctype html>
<html lang='fr'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Connexion admin | <?= e($siteName) ?></title>
    <meta name='robots' content='noindex,nofollow'>
    <meta name='referrer' content='strict-origin-when-cross-origin'>
    <meta name='theme-color' content='<?= e($themeColor) ?>'>
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&family=Raleway:wght@500;600;700;800;900&family=Roboto:wght@400;500;700&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>'>
    <style><?= ThemeService::cssVariables() ?></style>
    <link rel='stylesheet' href='<?= asset('css/main.css') ?>'>
    <link rel='stylesheet' href='<?= asset('css/admin.css') ?>'>
</head>
<body class='auth-body'>
<main class='auth-main'>
    <div class='auth-wrap'>
        <div class='auth-flashes'>
            <?php if ($msg = flash('success')): ?><div class='alert success'><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('warning')): ?><div class='alert warning'><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class='alert error'><?= e($msg) ?></div><?php endif; ?>
        </div>
        <?= $content ?>
    </div>
</main>
</body>
</html>