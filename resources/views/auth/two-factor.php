<?php
$pageTitle = 'V?rification 2FA';
$accountLabel = trim((string) ($pendingUser['email'] ?? ($pendingUser['name'] ?? 'administration')));
?>
<section class='auth-login-shell'>
    <div class='auth-login-showcase'>
        <div class='kicker'>V?rification 2FA</div>
        <h1>Un dernier code avant d'ouvrir le dashboard.</h1>
        <p class='lead'>Entre le code ? 6 chiffres g?n?r? par ton application d'authentification pour <strong><?= e($accountLabel !== '' ? $accountLabel : 'ton compte admin') ?></strong>.</p>

        <div class='auth-highlight-grid'>
            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-phone' aria-hidden='true'></i></span>
                <div>
                    <strong>Ouvre ton application</strong>
                    <p>Google Authenticator, Microsoft Authenticator, Authy ou une app TOTP ?quivalente.</p>
                </div>
            </article>

            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-123' aria-hidden='true'></i></span>
                <div>
                    <strong>Saisis le code du moment</strong>
                    <p>Le code change toutes les 30 secondes, sans SMS ni d?pendance externe.</p>
                </div>
            </article>

            <article class='auth-highlight-card'>
                <span class='auth-highlight-icon'><i class='bi bi-shield-lock' aria-hidden='true'></i></span>
                <div>
                    <strong>Remember me coup?</strong>
                    <p>Quand la 2FA est active, la restauration automatique de session est d?sactiv?e pour rester propre c?t? s?curit?.</p>
                </div>
            </article>
        </div>
    </div>

    <div class='auth-login-card'>
        <div class='auth-card-head'>
            <div class='kicker'>?tape 2 sur 2</div>
            <h2>V?rification du code</h2>
            <p class='lead'>Le mot de passe est valide. Il manque juste le code TOTP pour finaliser la connexion.</p>
        </div>

        <form class='form auth-form' method='post' action='<?= url('/admin/2fa/verify') ?>'>
            <?= csrf_field() ?>
            <label>
                <span class='label'>Code 2FA</span>
                <input class='input auth-code-input' type='text' name='code' inputmode='numeric' autocomplete='one-time-code' pattern='[0-9]*' maxlength='6' placeholder='123456' required>
            </label>

            <button class='btn auth-submit' type='submit'>Valider la v?rification</button>
            <a class='btn ghost' href='<?= url('/admin/login') ?>'>Retour ? la connexion</a>
        </form>
    </div>
</section>