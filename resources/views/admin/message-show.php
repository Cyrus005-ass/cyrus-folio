<?php $pageTitle = 'Message'; ?>
<?php
$messageSource = strtolower(trim((string) ($message['source'] ?? 'mysql')));
$messageSourceLabel = match ($messageSource) {
    'merged' => 'Archive + Live',
    'firestore', 'firebase' => 'Live Firestore',
    default => 'Archive MySQL',
};
?>
<div class='panel detail-shell'>
    <div class='split-line'>
        <div>
            <div class='kicker'>Message de contact</div>
            <h2><?= e($message['sujet'] ?? 'Message') ?></h2>
            <p class='meta'>Re?u le <?= e($message['created_at'] ?? '') ?></p>
            <p class='meta'>Flux <?= e($messageSourceLabel) ?></p>
        </div>
        <?php if (($message['statut'] ?? 'nouveau') === 'lu'): ?><span class='badge green'>Lu</span><?php else: ?><span class='badge red'>Non lu</span><?php endif; ?>
    </div>
    <div class='card' style='padding:16px;'>
        <p><strong>Nom :</strong> <?= e($message['nom'] ?? '') ?></p>
        <p><strong>Email :</strong> <?= e($message['email'] ?? '') ?></p>
        <p><strong>Sujet :</strong> <?= e($message['sujet'] ?? '') ?></p>
        <div class='rich-content'><?= nl2br(e($message['message'] ?? '')) ?></div>
    </div>
    <div class='actions'>
        <a class='btn ghost' href='<?= url('/admin/messages') ?>'>Retour</a>
        <a class='btn ghost' href='mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: ' . ($message['sujet'] ?? '')) ?>'>R?pondre</a>
        <form method='post' action='<?= url('/admin/messages/' . $message['id']) ?>'>
            <?= csrf_field() ?>
            <?= method_field('DELETE') ?>
            <button class='btn danger' type='submit' data-confirm='Supprimer ce message ?'>Supprimer</button>
        </form>
    </div>
</div>
