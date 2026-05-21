<?php use App\Services\ThemeService; ?>
<?php
$activeTheme = ThemeService::activeTheme();
$themeColor = trim((string) ($activeTheme['primary_color'] ?? '#ff4d4f'));
?>
<!doctype html>
<html lang='fr'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Admin - <?= e(env('APP_NAME', 'Cyrus-y ASSOGBA')) ?></title>
    <meta name='robots' content='noindex,nofollow'>
    <meta name='referrer' content='strict-origin-when-cross-origin'>
    <meta name='theme-color' content='<?= e($themeColor) ?>'>
    <?php if ($fontStylesheetUrl = ThemeService::fontStylesheetUrl($activeTheme)): ?>
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='<?= e($fontStylesheetUrl) ?>' rel='stylesheet'>
    <?php endif; ?>
    <style><?= ThemeService::cssVariables() ?></style>
    <link rel='stylesheet' href='<?= asset('css/main.css') ?>'>
    <link rel='stylesheet' href='<?= asset('css/admin.css') ?>'>
</head>
<body class='admin-body'>
<div class='admin-shell'>
    <?php require RESOURCE_PATH . '/components/sidebar.php'; ?>
    <main class='admin-main'>
        <?php $unreadNotifications = (new \App\Models\Notification())->unreadCount(); ?>
        <div class='topbar'>
            <div><div class='kicker'>Back-office</div><h1 style='margin:.2rem 0'><?= e($pageTitle ?? env('APP_NAME', 'Cyrus-y ASSOGBA')) ?></h1></div>
            <div class='actions'>
                <a class='btn ghost' href='<?= url('/admin/notifications') ?>'>Cloche<?php if ($unreadNotifications > 0): ?> <span class='badge blue'><?= $unreadNotifications ?></span><?php endif; ?></a>
                <a class='btn ghost' href='<?= url('/') ?>'>Voir le site</a>
                <form method='post' action='<?= url('/admin/logout') ?>'>
                    <?= csrf_field() ?>
                    <button class='btn secondary' type='submit'>Deconnexion</button>
                </form>
            </div>
        </div>
        <?php if ($msg = flash('success')): ?><div class='alert success'><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('warning')): ?><div class='alert warning'><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class='alert error'><?= e($msg) ?></div><?php endif; ?>
        <?= $content ?>
    </main>
</div>
<script src='<?= asset('js/main.js') ?>'></script>
<script src='<?= asset('js/admin.js') ?>'></script>
<script src='<?= asset('js/admin-messages-live.js') ?>'></script>
</body>
</html>
