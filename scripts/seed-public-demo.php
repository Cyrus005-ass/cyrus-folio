#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Services\SchemaService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('RESOURCE_PATH', BASE_PATH . '/resources');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once APP_PATH . '/Core/Env.php';
Env::load(BASE_PATH . '/.env');
if (is_file(BASE_PATH . '/.env.local')) {
    Env::load(BASE_PATH . '/.env.local');
}

define('APP_CONFIG', is_file(CONFIG_PATH . '/app.php') ? require CONFIG_PATH . '/app.php' : []);
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative) . '.php';
    $segments = explode('/', $relativePath);
    if ($segments !== []) {
        $segments[0] = match ($segments[0]) {
            'Controllers' => 'controllers',
            'Models' => 'models',
            'Services' => 'services',
            'Middleware' => 'middleware',
            default => $segments[0],
        };
    }

    $file = APP_PATH . '/' . implode('/', $segments);
    if (is_file($file)) {
        require_once $file;
    }
});

foreach (glob(APP_PATH . '/helpers/*.php') ?: [] as $helper) {
    require_once $helper;
}

SchemaService::ensureLatest();
$pdo = Database::connect();
$pdo->beginTransaction();

try {
    $media = demoMedia();
    $stats = [
        'profile' => seedProfile($media),
        'projects' => seedProjects($media),
        'posts' => seedPosts($media),
        'skills' => seedSkills(),
        'certifications' => seedCertifications($media),
    ];
    $stats['collaborations'] = seedCollaborations($stats['projects']['ids'] ?? []);

    $pdo->commit();

    $summary = [
        'status' => 'ok',
        'profile' => $stats['profile'],
        'projects' => (int) ($stats['projects']['count'] ?? 0),
        'posts' => (int) ($stats['posts']['count'] ?? 0),
        'skills' => $stats['skills'],
        'certifications' => $stats['certifications'],
        'collaborations' => (int) ($stats['collaborations']['count'] ?? 0),
        'visit' => [
            'home' => url('/'),
            'projects' => url('/projects'),
            'skills' => url('/skills'),
            'certifications' => url('/certifications'),
            'blog' => url('/blog'),
            'about' => url('/about'),
        ],
    ];

    fwrite(STDOUT, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function demoMedia(): array
{
    $sources = imageSources();
    return [
        'cover' => mirrorDemoAsset($sources, 'demo-cover.jpg'),
        'alt' => mirrorDemoAsset(array_reverse($sources), 'demo-alt.jpg'),
        'video' => is_file(public_path('assets/uploads/C-y.mp4')) ? 'assets/uploads/C-y.mp4' : null,
    ];
}

function imageSources(): array
{
    $sources = [];
    $rootImage = base_path('a.jpg');
    if (is_file($rootImage)) {
        $sources[] = $rootImage;
    }

    foreach (glob(public_path('assets/uploads/profile/*')) ?: [] as $file) {
        $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $sources[] = $file;
        }
    }

    return array_values(array_unique(array_filter($sources, 'is_file')));
}

function mirrorDemoAsset(array $sources, string $targetName): ?string
{
    foreach ($sources as $source) {
        if (!is_string($source) || !is_file($source)) {
            continue;
        }

        $relative = 'assets/uploads/demo/' . $targetName;
        $target = public_path($relative);
        $directory = dirname($target);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        copy($source, $target);
        return $relative;
    }

    return null;
}

function seedProfile(array $media): string
{
    $profile = Database::query('SELECT * FROM profiles ORDER BY id ASC LIMIT 1')->fetch();
    if (!$profile) {
        return 'skipped';
    }

    $updates = [];
    $bio = trim((string) ($profile['bio'] ?? ''));
    if ($bio === '' || $bio === 'Bio Codex de test' || $bio === 'Developpeur fullstack oriente produit, je construis des experiences web propres, administrables et pensees pour bien rendre sur desktop comme sur mobile.' || $bio === 'Développeur fullstack orienté produit, je construis des expériences web propres, administrables et pensées pour bien rendre sur desktop comme sur mobile.') {
        $updates['bio'] = 'Développeur fullstack orienté produit, je construis des expériences web propres, administrables et pensées pour bien rendre sur desktop comme sur mobile.';
    }
    if (trim((string) ($profile['presentation_video_url'] ?? '')) === '' && !empty($media['video'])) {
        $updates['presentation_video_url'] = (string) $media['video'];
    }

    if ($updates === []) {
        return 'unchanged';
    }

    updateRow('profiles', (int) $profile['id'], $updates);
    return 'updated';
}

function seedProjects(array $media): array
{
    $gallery = encode_json_array(array_values(array_filter([$media['cover'] ?? null, $media['alt'] ?? null])));
    $items = [
        [
            'titre' => 'Plateforme de gestion commerciale',
            'slug' => 'demo-plateforme-gestion-commerciale',
            'description' => 'Application web pour suivre les ventes, les clients, le stock et les indicateurs clés dans une interface simple à piloter.',
            'contenu' => "Objectif : centraliser le suivi commercial dans une interface lisible.\n\nFonctionnalités : tableau de bord, suivi des commandes, état du stock, fiches clients et vues rapides pour l'admin.\n\nCe projet de démo sert aussi à montrer le rendu des cartes, des tags et des pages détail projet côté public.",
            'technologies' => 'PHP, MySQL, JavaScript, Bootstrap',
            'image_url' => $media['cover'] ?? null,
            'gallery_images' => $gallery,
            'github_url' => 'https://github.com/Cyrus005-ass',
            'demo_url' => '/about',
            'statut' => 'publie',
            'est_mis_en_avant' => 1,
            'ordre' => 1,
        ],
        [
            'titre' => 'Portfolio administrable fullstack',
            'slug' => 'demo-portfolio-administrable-fullstack',
            'description' => 'Portfolio avec back-office léger pour gérer le profil, les projets, le blog, le theme et la messagerie.',
            'contenu' => "Objectif : publier du contenu sans toucher au code à chaque modification.\n\nFonctionnalités : édition du profil, CRUD projets, compétences, blog, theme central et espace admin responsive.\n\nC'est un bon projet pour juger le rendu public des sections hero, portfolio et about.",
            'technologies' => 'PHP MVC, MySQL, CSS, Admin UI',
            'image_url' => $media['alt'] ?? ($media['cover'] ?? null),
            'gallery_images' => $gallery,
            'github_url' => 'https://github.com/Cyrus005-ass',
            'demo_url' => '/skills',
            'statut' => 'publie',
            'est_mis_en_avant' => 1,
            'ordre' => 2,
        ],
        [
            'titre' => 'Centre de notifications live',
            'slug' => 'demo-centre-de-notifications-live',
            'description' => 'Module de notifications et messages synchronisés pour suivre les signaux importants et les nouvelles demandes.',
            'contenu' => "Objectif : garder une source archivée en base tout en exposant un affichage plus réactif.\n\nFonctionnalités : statut lu/non lu, tri des alertes, journal d activité et points de controle pour l'administration.\n\nLa fiche sert ici à montrer un rendu détail avec visuels et contenu plus dense.",
            'technologies' => 'PHP, Firebase, REST API, JSON',
            'image_url' => $media['cover'] ?? null,
            'gallery_images' => $gallery,
            'github_url' => 'https://github.com/Cyrus005-ass',
            'demo_url' => '/blog',
            'statut' => 'publie',
            'est_mis_en_avant' => 1,
            'ordre' => 3,
        ],
        [
            'titre' => 'Mini CRM terrain',
            'slug' => 'demo-mini-crm-terrain',
            'description' => 'Petit CRM de suivi prospects et rendez-vous, pensé pour une utilisation rapide sur mobile et desktop.',
            'contenu' => "Objectif : permettre un suivi simple des leads et des relances.\n\nFonctionnalités : pipeline commercial, rappels, notes internes et synthèse rapide des actions en cours.\n\nLe rendu public montre ici une carte projet sans surcharge visuelle.",
            'technologies' => 'PHP, MySQL, Responsive Design, Analytics',
            'image_url' => $media['alt'] ?? ($media['cover'] ?? null),
            'gallery_images' => $gallery,
            'github_url' => 'https://github.com/Cyrus005-ass',
            'demo_url' => '/contact',
            'statut' => 'publie',
            'est_mis_en_avant' => 0,
            'ordre' => 4,
        ],
    ];

    $ids = [];
    foreach ($items as $item) {
        $ids[$item['slug']] = upsertBySlug('projects', (string) $item['slug'], $item);
    }

    return ['count' => count($items), 'ids' => $ids];
}

function seedPosts(array $media): array
{
    $items = [
        [
            'titre' => 'Construire un portfolio administrable sans framework lourd',
            'category' => 'backend',
            'slug' => 'demo-construire-un-portfolio-administrable',
            'extrait' => 'Retour d\'expérience sur une architecture PHP légère, claire à maintenir et assez souple pour un vrai back-office.',
            'contenu' => '<p>Un portfolio administrable ne se limite pas à un beau design. Il faut aussi une structure simple à maintenir, des formulaires propres et des vues publiques qui ne se dégradent pas dès que le contenu change.</p><h2>Ce qui aide vraiment</h2><ul><li>Des routes lisibles</li><li>Des formulaires admin clairs</li><li>Des cartes publiques qui tiennent sans image parfaite</li></ul><p>Cette note de démo sert surtout à remplir la page blog avec un vrai rythme éditorial.</p>',
            'tags' => 'PHP, Architecture, Admin',
            'image_url' => $media['alt'] ?? ($media['cover'] ?? null),
            'statut' => 'publie',
            'published_at' => date('Y-m-d H:i:s', strtotime('-24 days')),
            'view_count' => random_int(18, 95),
        ],
        [
            'titre' => 'Rendre un dashboard admin vraiment utile',
            'category' => 'productivite',
            'slug' => 'demo-rendre-un-dashboard-admin-vraiment-utile',
            'extrait' => 'Un dashboard doit faire gagner du temps, pas juste afficher des compteurs décoratifs.',
            'contenu' => '<p>Quand un dashboard remonte les brouillons, les messages, les alertes et la sante systeme, il devient un outil de pilotage et pas seulement une page d accueil admin.</p><h2>Les blocs qui comptent</h2><p>Inbox, file editoriale, trafic recent, activité et checks techniques. Avec ca, on sait vite ou agir.</p>',
            'tags' => 'Dashboard, UX, Admin',
            'image_url' => $media['cover'] ?? null,
            'statut' => 'publie',
            'published_at' => date('Y-m-d H:i:s', strtotime('-17 days')),
            'view_count' => random_int(32, 140),
        ],
        [
            'titre' => 'Connecter un module de messages à Firebase sans perdre MySQL',
            'category' => 'integration',
            'slug' => 'demo-connecter-messages-firebase-mysql',
            'extrait' => 'Une approche hybride pour garder l\'archive locale tout en testant une couche live.',
            'contenu' => '<p>Un module messages peut très bien garder MySQL comme archive et utiliser une couche live pour les lectures rapides ou la synchro temps reel.</p><h2>Pourquoi c\'est utile</h2><p>On se donne un terrain de test moderne sans casser le coeur du backend existant.</p>',
            'tags' => 'Firebase, MySQL, API',
            'image_url' => $media['alt'] ?? ($media['cover'] ?? null),
            'statut' => 'publie',
            'published_at' => date('Y-m-d H:i:s', strtotime('-11 days')),
            'view_count' => random_int(21, 110),
        ],
        [
            'titre' => 'Soigner le rendu mobile avant la mise en prod',
            'category' => 'frontend',
            'slug' => 'demo-soigner-le-rendu-mobile-avant-la-mise-en-prod',
            'extrait' => 'Les détails mobiles changent beaucoup la perception d\'un portfolio public.',
            'contenu' => '<p>Le menu, les cartes, les champs de formulaire et la hierarchie visuelle doivent rester solides sur petit écran. C est souvent la différence entre un site joli et un site vraiment présentable.</p><p>Cette publication sert aussi a peupler les grilles blog et les pages detail article.</p>',
            'tags' => 'Responsive, Mobile, UI',
            'image_url' => $media['cover'] ?? null,
            'statut' => 'publie',
            'published_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            'view_count' => random_int(12, 80),
        ],
    ];

    foreach ($items as $item) {
        upsertBySlug('posts', (string) $item['slug'], $item);
    }

    return ['count' => count($items)];
}

function seedSkills(): array
{
    Database::query('UPDATE skills SET nom = ? WHERE nom = ?', ['Mise en page éditoriale', 'Mise en page editoriale']);

    if (tableCount('skills') > 0) {
        return ['count' => 0, 'status' => 'skipped_existing'];
    }

    $items = [
        ['nom' => 'PHP backend', 'categorie' => 'Langages', 'niveau' => 'Expert', 'description' => 'Backends PHP légers, lisibles et faciles à faire évoluer.', 'est_active' => 1, 'ordre' => 1],
        ['nom' => 'JavaScript moderne', 'categorie' => 'Langages', 'niveau' => 'Avance', 'description' => 'Interactions front, logique UI et petits modules client.', 'est_active' => 1, 'ordre' => 2],
        ['nom' => 'SQL / MySQL', 'categorie' => 'Langages', 'niveau' => 'Avance', 'description' => 'Schémas simples, requêtes claires et données bien structurées.', 'est_active' => 1, 'ordre' => 3],
        ['nom' => 'Bootstrap 5', 'categorie' => 'Frameworks', 'niveau' => 'Avance', 'description' => 'Interfaces rapides à mettre en place puis à affiner.', 'est_active' => 1, 'ordre' => 4],
        ['nom' => 'Architecture MVC', 'categorie' => 'Frameworks', 'niveau' => 'Avance', 'description' => 'Organisation propre des routes, contrôleurs, services et vues.', 'est_active' => 1, 'ordre' => 5],
        ['nom' => 'Responsive UI', 'categorie' => 'Frameworks', 'niveau' => 'Expert', 'description' => 'Rendus propres sur desktop, tablette et mobile.', 'est_active' => 1, 'ordre' => 6],
        ['nom' => 'Git / GitHub', 'categorie' => 'Outils', 'niveau' => 'Avance', 'description' => 'Versionning, revue de code et itérations rapides.', 'est_active' => 1, 'ordre' => 7],
        ['nom' => 'Firebase', 'categorie' => 'Outils', 'niveau' => 'Intermediaire', 'description' => 'Auth admin, sync messages et services cloud de support.', 'est_active' => 1, 'ordre' => 8],
        ['nom' => 'Validation & CSRF', 'categorie' => 'Securite', 'niveau' => 'Avance', 'description' => 'Protection des formulaires et hygiène de base côté serveur.', 'est_active' => 1, 'ordre' => 9],
        ['nom' => 'Auth admin', 'categorie' => 'Securite', 'niveau' => 'Avance', 'description' => 'Session, remember me, limitation de tentatives et 2FA.', 'est_active' => 1, 'ordre' => 10],
        ['nom' => 'SEO technique', 'categorie' => 'Autre', 'niveau' => 'Intermediaire', 'description' => 'Métas, Open Graph, images sociales et structure de page.', 'est_active' => 1, 'ordre' => 11],
        ['nom' => 'Mise en page éditoriale', 'categorie' => 'Autre', 'niveau' => 'Avance', 'description' => 'Cartes, grilles et hiérarchie visuelle pour le contenu public.', 'est_active' => 1, 'ordre' => 12],
    ];

    foreach ($items as $item) {
        insertRow('skills', $item);
    }

    return ['count' => count($items), 'status' => 'inserted'];
}

function seedCertifications(array $media): array
{
    if (tableCount('certifications') > 0) {
        return ['count' => 0, 'status' => 'skipped_existing'];
    }

    $badge = $media['alt'] ?? ($media['cover'] ?? null);
    $items = [
        ['titre' => 'Responsive Web Design', 'organisme' => 'freeCodeCamp', 'date_obtention' => date('Y-m-d', strtotime('-420 days')), 'date_expiration' => null, 'credential_id' => 'FCC-RWD-DEMO', 'badge_url' => $badge, 'lien_verification' => 'https://www.freecodecamp.org/learn/', 'est_active' => 1, 'ordre' => 1],
        ['titre' => 'JavaScript Algorithms and Data Structures', 'organisme' => 'freeCodeCamp', 'date_obtention' => date('Y-m-d', strtotime('-320 days')), 'date_expiration' => null, 'credential_id' => 'FCC-JS-DEMO', 'badge_url' => $badge, 'lien_verification' => 'https://www.freecodecamp.org/learn/javascript-algorithms-and-data-structures-v8/', 'est_active' => 1, 'ordre' => 2],
        ['titre' => 'Foundations of User Experience', 'organisme' => 'Coursera', 'date_obtention' => date('Y-m-d', strtotime('-210 days')), 'date_expiration' => date('Y-m-d', strtotime('+365 days')), 'credential_id' => 'COURSE-UX-DEMO', 'badge_url' => $badge, 'lien_verification' => 'https://www.coursera.org/', 'est_active' => 1, 'ordre' => 3],
    ];

    foreach ($items as $item) {
        insertRow('certifications', $item);
    }

    return ['count' => count($items), 'status' => 'inserted'];
}

function seedCollaborations(array $projectIds): array
{
    $items = [
        'demo-plateforme-gestion-commerciale' => [
            ['nom_membre' => 'Amandine K.', 'role' => 'UI Designer', 'email' => 'amandine.demo@example.com', 'linkedin_url' => 'https://linkedin.com/', 'portfolio_url' => 'https://github.com/Cyrus005-ass', 'github_url' => 'https://github.com/Cyrus005-ass', 'contribution' => 'Maquettes et hierarchie des ecrans principaux.'],
            ['nom_membre' => 'Jean-Marc A.', 'role' => 'QA / Tests', 'email' => 'jeanmarc.demo@example.com', 'linkedin_url' => 'https://linkedin.com/', 'portfolio_url' => '', 'github_url' => 'https://github.com/Cyrus005-ass', 'contribution' => 'Verification fonctionnelle et scenarios de validation.'],
        ],
        'demo-portfolio-administrable-fullstack' => [
            ['nom_membre' => 'Ruth B.', 'role' => 'Content Strategist', 'email' => 'ruth.demo@example.com', 'linkedin_url' => 'https://linkedin.com/', 'portfolio_url' => '', 'github_url' => '', 'contribution' => 'Structuration des textes et des sections publiques.'],
        ],
        'demo-centre-de-notifications-live' => [
            ['nom_membre' => 'Kevin T.', 'role' => 'Integrateur API', 'email' => 'kevin.demo@example.com', 'linkedin_url' => 'https://linkedin.com/', 'portfolio_url' => '', 'github_url' => 'https://github.com/Cyrus005-ass', 'contribution' => 'Branchement des endpoints et scenarios de synchronisation.'],
        ],
    ];

    $count = 0;
    foreach ($items as $slug => $members) {
        $projectId = (int) ($projectIds[$slug] ?? 0);
        if ($projectId <= 0) {
            continue;
        }

        foreach ($members as $member) {
            $existing = Database::query('SELECT id FROM collaborations WHERE project_id = ? AND nom_membre = ? LIMIT 1', [$projectId, $member['nom_membre']])->fetch();
            $payload = array_merge(['project_id' => $projectId], $member);
            if ($existing) {
                updateRow('collaborations', (int) $existing['id'], $payload);
            } else {
                insertRow('collaborations', $payload);
            }
            $count++;
        }
    }

    return ['count' => $count];
}

function upsertBySlug(string $table, string $slug, array $payload): int
{
    $existing = Database::query("SELECT id FROM {$table} WHERE slug = ? LIMIT 1", [$slug])->fetch();
    if ($existing) {
        updateRow($table, (int) $existing['id'], $payload);
        return (int) $existing['id'];
    }

    return insertRow($table, $payload);
}

function insertRow(string $table, array $payload): int
{
    $columns = array_keys($payload);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), $placeholders);
    Database::query($sql, array_values($payload));
    return (int) Database::lastInsertId();
}

function updateRow(string $table, int $id, array $payload): void
{
    $columns = array_keys($payload);
    $sets = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', $columns));
    $sql = sprintf('UPDATE %s SET %s WHERE id = ?', $table, $sets);
    $params = array_values($payload);
    $params[] = $id;
    Database::query($sql, $params);
}

function tableCount(string $table): int
{
    return (int) (Database::query("SELECT COUNT(*) total FROM {$table}")->fetch()['total'] ?? 0);
}





