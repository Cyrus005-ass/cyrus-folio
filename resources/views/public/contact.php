<?php $socialLinks = profile_social_links($profile ?? null); ?>

<section class='section page-shell'>
    <div class='container split-panels'>
        <div class='panel-block'>
            <div class='kicker'>Contact</div>
            <h1>Parlons de ton projet</h1>
            <p class='lead'>Utilise ce formulaire pour une mission, une collaboration ou une prise de contact simple.</p>
            <div class='stack-list'>
                <?php if (!empty($profile['email'])): ?><p><strong>Email :</strong> <?= e($profile['email']) ?></p><?php endif; ?>
                <?php if (!empty($profile['phone'])): ?><p><strong>Téléphone :</strong> <?= e($profile['phone']) ?></p><?php endif; ?>
                <?php if (!empty($profile['location'])): ?><p><strong>Localisation :</strong> <?= e($profile['location']) ?></p><?php endif; ?>
                <?php if ($socialLinks !== []): ?>
                    <div>
                        <strong>Réseaux :</strong>
                        <div class='button-row'>
                            <?php foreach ($socialLinks as $link): ?>
                                <a class='btn ghost' href='<?= e($link['url']) ?>' target='_blank' rel='noreferrer'><?= e($link['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class='panel-block'>
            <form class='form' method='post' action='<?= url('/contact') ?>'>
                <?= csrf_field() ?>
                <label><span class='label'>Nom</span><input class='input' type='text' name='nom' required></label>
                <label><span class='label'>Email</span><input class='input' type='email' name='email' required></label>
                <label><span class='label'>Sujet</span><input class='input' type='text' name='sujet' required></label>
                <label><span class='label'>Message</span><textarea class='textarea' name='message' required></textarea></label>
                <button class='btn' type='submit'>Envoyer</button>
            </form>
        </div>
    </div>
</section>
