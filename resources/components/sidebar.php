<?php
$unreadMessages = (new \App\Models\Contact())->unreadCount();
$unreadNotifications = (new \App\Models\Notification())->unreadCount();
?>
<aside class='sidebar'>
    <div class='sidebar-head'>
        <a class='brand' href='<?= url('/admin') ?>'>C-Y <span>ASS</span></a>
        <button
            class='sidebar-toggle'
            type='button'
            aria-expanded='false'
            aria-controls='admin-sidebar-nav'
            aria-label='Afficher le menu admin'
            data-admin-sidebar-toggle
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    <nav id='admin-sidebar-nav' class='sidebar-nav' data-admin-sidebar-nav>
        <a class='<?= active_class('/admin') ?>' href='<?= url('/admin') ?>'>Dashboard</a>
        <a class='<?= active_class('/admin/projects') ?>' href='<?= url('/admin/projects') ?>'>Projets</a>
        <a class='<?= active_class('/admin/skills') ?>' href='<?= url('/admin/skills') ?>'>Comp?tences</a>
        <a class='<?= active_class('/admin/certifications') ?>' href='<?= url('/admin/certifications') ?>'>Certifications</a>
        <a class='<?= active_class('/admin/blog') ?>' href='<?= url('/admin/blog') ?>'>Blog</a>
        <a class='<?= active_class('/admin/collaborations') ?>' href='<?= url('/admin/collaborations') ?>'>Collaborations</a>
        <a class='<?= active_class('/admin/messages') ?>' href='<?= url('/admin/messages') ?>'>Messages<?php if ($unreadMessages > 0): ?> <span class='badge red'><?= $unreadMessages ?></span><?php endif; ?></a>
        <a class='<?= active_class('/admin/theme') ?>' href='<?= url('/admin/theme') ?>'>Th?me</a>
        <a class='<?= active_class('/admin/chatbot') ?>' href='<?= url('/admin/chatbot') ?>'>Chatbot</a>
        <a class='<?= active_class('/admin/notifications') ?>' href='<?= url('/admin/notifications') ?>'>Notifications<?php if ($unreadNotifications > 0): ?> <span class='badge blue'><?= $unreadNotifications ?></span><?php endif; ?></a>
        <a class='<?= active_class('/admin/analytics') ?>' href='<?= url('/admin/analytics') ?>'>Analytiques</a>
        <a class='<?= active_class('/admin/profile') ?>' href='<?= url('/admin/profile') ?>'>Profil</a>
    </nav>
</aside>
