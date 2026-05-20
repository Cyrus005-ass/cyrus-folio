<?php $pageTitle = 'Chatbot'; ?>
<section class='admin-grid'>
    <div class='panel'>
        <h2>Ajouter une connaissance</h2>
        <form class='form' method='post' action='<?= url('/admin/chatbot/knowledge') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Question</span><input class='input' type='text' name='question' required></label>
            <label><span class='label'>Reponse</span><textarea class='textarea' name='answer' required></textarea></label>
            <label><span class='label'>Mots-cles</span><input class='input' type='text' name='keywords'></label>
            <label><span class='label'>Active</span><select class='select' name='is_active'><option value='1'>Oui</option><option value='0'>Non</option></select></label>
            <button class='btn' type='submit'>Ajouter</button>
        </form>
    </div>
    <div class='panel'>
        <h2>Tester le chatbot</h2>
        <form class='form' method='post' action='<?= url('/admin/chatbot/test') ?>'>
            <?= csrf_field() ?>
            <label><span class='label'>Question de test</span><input class='input' type='text' name='message' value='<?= e($testQuestion ?? '') ?>' required></label>
            <button class='btn ghost' type='submit'>Tester</button>
        </form>
        <?php if (!empty($testAnswer)): ?>
            <div class='mini-card' style='margin-top:16px;'>
                <strong>Reponse</strong>
                <p class='meta'><?= e($testAnswer) ?></p>
                <?php if (!empty($testSource) || !empty($testRemoteStatus)): ?>
                    <p class='meta'>Source : <?= e($testSource ?: 'local') ?><?php if (!empty($testRemoteStatus)): ?> | Groq : <?= e($testRemoteStatus) ?><?php endif; ?><?php if (!empty($testRemoteCode)): ?> (<?= e($testRemoteCode) ?>)<?php endif; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<section class='panel' style='margin-top:20px;'>
    <h2>Base de connaissance</h2>
    <?php if (!empty($items)): ?>
        <div class='stack-list'>
            <?php foreach ($items as $item): ?>
                <form class='form card' method='post' action='<?= url('/admin/chatbot/knowledge/' . $item['id']) ?>' style='padding:16px;'>
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <label><span class='label'>Question</span><input class='input' type='text' name='question' value='<?= e($item['question']) ?>' required></label>
                    <label><span class='label'>Reponse</span><textarea class='textarea' name='answer' required><?= e($item['answer'] ?? '') ?></textarea></label>
                    <div class='form-row'>
                        <label><span class='label'>Mots-cles</span><input class='input' type='text' name='keywords' value='<?= e($item['keywords'] ?? '') ?>'></label>
                        <label><span class='label'>Active</span><select class='select' name='is_active'><option value='1' <?= !empty($item['is_active']) ? 'selected' : '' ?>>Oui</option><option value='0' <?= isset($item['is_active']) && !$item['is_active'] ? 'selected' : '' ?>>Non</option></select></label>
                    </div>
                    <div class='actions'>
                        <button class='btn ghost' type='submit'>Mettre a jour</button>
                    </div>
                </form>
                <form method='post' action='<?= url('/admin/chatbot/knowledge/' . $item['id']) ?>'>
                    <?= csrf_field() ?>
                    <?= method_field('DELETE') ?>
                    <button class='btn danger' type='submit' data-confirm='Supprimer cette entree ?'>Supprimer</button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class='empty'>Aucune connaissance pour le moment.</div>
    <?php endif; ?>
</section>
