<?php
$pageTitle = 'Th?me';
$defaults = theme_defaults();
$themeData = array_merge($defaults, is_array($theme ?? null) ? $theme : []);
?>
<section class='admin-grid'>
    <div class='panel'>
        <form class='form' method='post' action='<?= url('/admin/theme') ?>' data-theme-editor>
            <?= csrf_field() ?>
            <label><span class='label'>Nom</span><input class='input' type='text' name='nom' value='<?= e($themeData['nom']) ?>'></label>
            <div class='form-row'>
                <label><span class='label'>Couleur primaire</span><input class='input' type='color' name='primary_color' value='<?= e($themeData['primary_color']) ?>'></label>
                <label><span class='label'>Couleur secondaire</span><input class='input' type='color' name='secondary_color' value='<?= e($themeData['secondary_color']) ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Accent</span><input class='input' type='color' name='accent_color' value='<?= e($themeData['accent_color']) ?>'></label>
                <label><span class='label'>Fond</span><input class='input' type='color' name='background_color' value='<?= e($themeData['background_color']) ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Texte</span><input class='input' type='color' name='text_color' value='<?= e($themeData['text_color']) ?>'></label>
                <label><span class='label'>Police display</span><input class='input' type='text' name='display_font_family' value='<?= e($themeData['display_font_family']) ?>'></label>
            </div>
            <div class='form-row'>
                <label><span class='label'>Police body</span><input class='input' type='text' name='body_font_family' value='<?= e($themeData['body_font_family']) ?>'></label>
                <label><span class='label'>Animations</span><select class='select' name='animations_enabled'><option value='1' <?= !empty($themeData['animations_enabled']) ? 'selected' : '' ?>>Oui</option><option value='0' <?= isset($themeData['animations_enabled']) && !$themeData['animations_enabled'] ? 'selected' : '' ?>>Non</option></select></label>
            </div>
            <div class='actions'>
                <button class='btn' type='submit'>Appliquer</button>
            </div>
        </form>
        <form method='post' action='<?= url('/admin/theme/reset') ?>' style='margin-top:12px;'>
            <?= csrf_field() ?>
            <button class='btn ghost' type='submit'>R?initialiser</button>
        </form>
    </div>

    <div class='panel'>
        <h2>Pr?visualisation</h2>
        <p class='meta'>Cet aper?u reprend le header, les cartes, le hero, le menu mobile, les r?seaux et la chatbox du site public.</p>
        <div class='card theme-preview-card' data-theme-preview style='background:linear-gradient(135deg, <?= e($themeData['secondary_color']) ?> 0%, <?= e($themeData['background_color']) ?> 56%, <?= e($themeData['primary_color']) ?> 100%);color:<?= e($themeData['text_color']) ?>;border:1px solid rgba(255,255,255,0.08);'>
            <div class='theme-preview-shell' data-preview-shell>
                <strong data-preview-display style='font-family:<?= e($themeData['display_font_family']) ?>;color:<?= e($themeData['text_color']) ?>;'>C-Y</strong>
                <span class='kicker' data-preview-accent style='margin:0;'>Portfolio public</span>
            </div>

            <div class='theme-preview-stack'>
                <div>
                    <div class='kicker' data-preview-accent>Th?me live</div>
                    <h2 data-preview-display style='margin:12px 0 10px;font-family:<?= e($themeData['display_font_family']) ?>;color:<?= e($themeData['text_color']) ?>;'>Un th?me appliqu? partout</h2>
                    <p data-preview-body data-preview-muted style='margin:0;font-family:<?= e($themeData['body_font_family']) ?>;color:<?= e($themeData['text_color']) ?>;'>Header, hero, cartes, menu mobile, r?seaux et chatbox utilisent ces r?glages apr?s sauvegarde.</p>
                </div>

                <div class='button-row'>
                    <span class='btn' data-preview-primary>Bouton principal</span>
                    <span class='btn ghost' data-preview-ghost>Bouton secondaire</span>
                </div>

                <div class='theme-preview-grid'>
                    <div class='theme-preview-surface' data-preview-surface>
                        <strong style='display:block;margin-bottom:10px;'>Pages</strong>
                        <div class='tags'>
                            <span class='tag' data-preview-chip>Accueil</span>
                            <span class='tag' data-preview-chip>? propos</span>
                            <span class='tag' data-preview-chip>Contact</span>
                        </div>
                    </div>
                    <div class='theme-preview-surface' data-preview-surface>
                        <strong style='display:block;margin-bottom:10px;'>Composants</strong>
                        <p data-preview-muted style='margin:0;'>Le rendu pilote aussi le menu mobile, les r?seaux en ic?nes et la chatbox.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
