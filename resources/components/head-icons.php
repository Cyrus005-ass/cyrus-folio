<?php
$iconConfig = app_config('icons', []);
$iconThemeColor = trim((string) ($themeColor ?? app_config('theme_color', '#2563eb')));

$iconHref = static function (?string $path): string {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    return absolute_url($path) ?? '';
};

$faviconIco = $iconHref($iconConfig['favicon_ico'] ?? 'favicon.ico');
$faviconSvg = $iconHref($iconConfig['favicon_svg'] ?? 'assets/icons/favicon.svg');
$favicon32 = $iconHref($iconConfig['favicon_32'] ?? 'assets/icons/favicon-32x32.png');
$favicon16 = $iconHref($iconConfig['favicon_16'] ?? 'assets/icons/favicon-16x16.png');
$appleTouchIcon = $iconHref($iconConfig['apple_touch'] ?? 'assets/icons/apple-touch-icon.png');
$maskIcon = $iconHref($iconConfig['mask_icon'] ?? 'assets/icons/safari-pinned-tab.svg');
$webManifest = $iconHref($iconConfig['manifest'] ?? 'assets/icons/site.webmanifest');
?>
<?php if ($faviconIco !== ''): ?><link rel='icon' type='image/x-icon' href='<?= e($faviconIco) ?>'><?php endif; ?>
<?php if ($faviconSvg !== ''): ?><link rel='icon' type='image/svg+xml' href='<?= e($faviconSvg) ?>'><?php endif; ?>
<?php if ($favicon32 !== ''): ?><link rel='icon' type='image/png' sizes='32x32' href='<?= e($favicon32) ?>'><?php endif; ?>
<?php if ($favicon16 !== ''): ?><link rel='icon' type='image/png' sizes='16x16' href='<?= e($favicon16) ?>'><?php endif; ?>
<?php if ($appleTouchIcon !== ''): ?><link rel='apple-touch-icon' sizes='180x180' href='<?= e($appleTouchIcon) ?>'><?php endif; ?>
<?php if ($maskIcon !== ''): ?><link rel='mask-icon' href='<?= e($maskIcon) ?>' color='<?= e($iconThemeColor) ?>'><?php endif; ?>
<?php if ($webManifest !== ''): ?><link rel='manifest' href='<?= e($webManifest) ?>'><?php endif; ?>
<meta name='msapplication-TileColor' content='<?= e($iconThemeColor) ?>'>
