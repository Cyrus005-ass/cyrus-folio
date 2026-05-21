<?php
if (!function_exists('url') || !function_exists('csrf_field')) {
    require_once dirname(__DIR__, 3) . '/app/Core/Bootstrap.php';
}

$siteName = trim((string) env('APP_NAME', 'Cyrus-y ASSOGBA'));
$logoVideoUrl = is_file(public_path('assets/uploads/C-y.mp4')) ? asset('uploads/C-y.mp4') : '';
$useLogoVideo = $logoVideoUrl !== '' && !save_data_enabled();
?>

<section class='auth-login-shell'>
    <div class='auth-login-showcase'>
        <a class='auth-brand' href='<?= url('/') ?>'>
            <?php if ($useLogoVideo): ?>
                <span class='brand-emblem' aria-hidden='true'>
                    <video class='brand-logo-video' autoplay muted loop playsinline preload='metadata'>
                        <source src='<?= e($logoVideoUrl) ?>' type='video/mp4'>
                    </video>
                </span>
            <?php else: ?>
                <span class='brand-mark' aria-hidden='true'>C-Y</span>
            <?php endif; ?>
            <span class='auth-brand-copy'>
                <strong>C-Y</strong>
                <small><?= e($siteName) ?></small>
            </span>
        </a>

        <div class='kicker'>Back-office priv?</div>
        <h1>Pilote ton portfolio depuis un espace net, premium et rapide.</h1>
        <p class='lead'>Connecte-toi pour g?rer le profil, les projets, les comp?tences, le th?me, les r?seaux sociaux, les messages et la base de connaissance du chatbot.</p>

        <div class='auth-highlight-grid'>
            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-person-badge' aria-hidden='true'></i></span>
                <div>
                    <strong>Profil centralis?</strong>
                    <p>Nom, bio, photo, CV, vid?o, disponibilit? et r?seaux au m?me endroit.</p>
                </div>
            </article>

            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-grid-1x2' aria-hidden='true'></i></span>
                <div>
                    <strong>Contenu administrable</strong>
                    <p>Projets, comp?tences, blog, certifications et collaborations sans toucher au code.</p>
                </div>
            </article>

            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-palette' aria-hidden='true'></i></span>
                <div>
                    <strong>Th?me synchronis?</strong>
                    <p>Le dashboard et le site public gardent la m?me direction visuelle.</p>
                </div>
            </article>

            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-robot' aria-hidden='true'></i></span>
                <div>
                    <strong>Assistant intelligent</strong>
                    <p>Le chatbot r?pond via Groq quand disponible ou via un fallback local structur?.</p>
                </div>
            </article>
        </div>

        <div class='auth-links'>
            <a class='btn ghost' href='<?= url('/') ?>'>Voir le site</a>
            <a class='btn ghost' href='<?= url('/contact') ?>'>Page contact</a>
        </div>
    </div>

    <div class='auth-login-card'>
        <div class='auth-card-head'>
            <div class='kicker'>Connexion admin</div>
            <h2>Bienvenue</h2>
            <p class='lead'>Entre tes identifiants pour acc?der au dashboard et administrer le portfolio.</p>
        </div>

        <form class='form auth-form' method='post' action='<?= url('/admin/login') ?>'>
            <?= csrf_field() ?>
            <label>
                <span class='label'>Adresse email</span>
                <input class='input' type='email' name='email' autocomplete='username' placeholder='admin@portfolio.local' required>
            </label>

            <label>
                <span class='label'>Mot de passe</span>
                <input class='input' type='password' name='password' autocomplete='current-password' placeholder='Votre mot de passe' required>
            </label>

            <div class='auth-check'>
                <label class='auth-remember'>
                    <input type='checkbox' name='remember' value='1'>
                    <span>Se souvenir de moi</span>
                </label>
                <span class='auth-check-note'>Acc?s r?serv? ? l'administration</span>
            </div>

            <button class='btn auth-submit' type='submit'>Entrer dans le dashboard</button>
        </form>

        <div class='auth-helper'>
            <span class='auth-helper-icon'><i class='bi bi-shield-lock' aria-hidden='true'></i></span>
            <p>Cette page sert uniquement ? administrer le portfolio : contenu, th?me, analytics, messages et configuration du chatbot.</p>
        </div>
    </div>
</section>
