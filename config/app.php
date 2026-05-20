<?php

use App\Core\Env;

$environment = (string) Env::get('APP_ENV', 'local');

return [
    'name' => Env::get('APP_NAME', 'Cyrus-y ASSOGBA'),
    'url' => Env::get('APP_URL', 'http://localhost/portfolio-os'),
    'env' => $environment,
    'debug' => Env::get('APP_DEBUG', false),
    'locale' => Env::get('APP_LOCALE', 'fr_FR'),
    'author' => Env::get('APP_AUTHOR', Env::get('APP_NAME', 'Cyrus-y ASSOGBA')),
    'description' => Env::get('APP_DESCRIPTION', 'Portfolio officiel de Cyrus-y ASSOGBA, developpeur fullstack base a Cotonou.'),
    'keywords' => Env::get('APP_KEYWORDS', 'portfolio, Cyrus-y ASSOGBA, developpeur fullstack, web, projets, certifications'),
    'robots' => Env::get('APP_ROBOTS', strtolower($environment) === 'production' ? 'index,follow' : 'noindex,nofollow'),
    'og_image' => Env::get('APP_OG_IMAGE', ''),
    'twitter_handle' => Env::get('APP_TWITTER_HANDLE', ''),
    'theme_color' => Env::get('APP_THEME_COLOR', '#2563eb'),
];
