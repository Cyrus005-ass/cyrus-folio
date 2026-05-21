<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Activity;
use App\Models\Analytics;
use App\Models\Certification;
use App\Models\ChatbotKnowledge;
use App\Models\Collaboration;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Project;
use App\Models\Skill;
use App\Services\NotificationService;
use App\Services\SystemHealthService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        NotificationService::syncCertificationAlerts();

        $analytics = new Analytics();
        $projects = new Project();
        $posts = new Post();
        $skills = new Skill();
        $certifications = new Certification();
        $contacts = new Contact();
        $notifications = new Notification();
        $chatbotKnowledge = new ChatbotKnowledge();
        $collaborations = new Collaboration();

        $messageAlertDays = max(1, (int) env('DASHBOARD_UNREAD_ALERT_DAYS', 3));
        $analyticsSummary = $analytics->summary(30);

        $publishedProjectsCount = $projects->count("statut = 'publie'");
        $draftProjectsCount = $projects->count("statut = 'brouillon'");
        $publishedPostsCount = $posts->count("statut = 'publie'");
        $draftPostsCount = $posts->count("statut = 'brouillon'");
        $unreadMessagesCount = $contacts->unreadCount();
        $unreadNotificationsCount = $notifications->unreadCount();
        $activeCertificationsCount = $certifications->count('est_active = 1 AND (date_expiration IS NULL OR date_expiration >= CURDATE())');
        $activeSkillsCount = $skills->count('est_active = 1');
        $knowledgeCount = $chatbotKnowledge->count('is_active = 1');
        $collaborationCount = $collaborations->count();

        $oldUnreadMessages = Database::query(
            'SELECT * FROM contacts WHERE statut = \'nouveau\' AND created_at <= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY created_at ASC LIMIT 5',
            [$messageAlertDays]
        )->fetchAll();

        $recentActivities = Database::query(
            'SELECT activities.*, users.name AS user_name FROM activities LEFT JOIN users ON users.id = activities.user_id ORDER BY activities.created_at DESC LIMIT 10'
        )->fetchAll();

        $this->view('admin/dashboard', [
            'counts' => [
                'visitors_month' => $analytics->monthVisitors(),
                'total_visits' => (int) ($analyticsSummary['total_visits'] ?? 0),
                'published_projects' => $publishedProjectsCount,
                'editorial_backlog' => $draftProjectsCount + $draftPostsCount,
                'unread_messages' => $unreadMessagesCount,
                'unread_notifications' => $unreadNotificationsCount,
                'active_certifications' => $activeCertificationsCount,
                'knowledge_base' => $knowledgeCount,
            ],
            'timeline' => $analytics->timeline(30),
            'expiringCertifications' => array_slice($certifications->expiringSoon(30), 0, 5),
            'oldUnreadMessages' => $oldUnreadMessages,
            'recentUnreadMessages' => array_slice($contacts->all('created_at DESC', "statut = 'nouveau'"), 0, 5),
            'draftProjects' => array_slice($projects->all('updated_at DESC, created_at DESC', "statut = 'brouillon'"), 0, 4),
            'draftPosts' => array_slice($posts->all('updated_at DESC, created_at DESC', "statut = 'brouillon'"), 0, 4),
            'notifications' => array_slice($notifications->all('created_at DESC'), 0, 6),
            'activities' => $recentActivities,
            'analyticsSummary' => $analyticsSummary,
            'traffic' => [
                'topPages' => $analytics->pages(30, 5),
                'devices' => $analytics->devices(30),
                'countries' => $analytics->countries(30, 4),
            ],
            'quickActions' => [
                ['label' => 'Nouveau projet', 'path' => '/admin/projects/create', 'style' => ''],
                ['label' => 'Nouvel article', 'path' => '/admin/blog/create', 'style' => 'ghost'],
                ['label' => 'Lire les messages', 'path' => '/admin/messages', 'style' => 'ghost'],
                ['label' => 'Profil public', 'path' => '/admin/profile', 'style' => 'ghost'],
                ['label' => 'Th?me', 'path' => '/admin/theme', 'style' => 'ghost'],
            ],
            'moduleSnapshots' => [
                ['label' => 'Projets', 'total' => $projects->count(), 'detail' => $publishedProjectsCount . ' publi?s, ' . $draftProjectsCount . ' brouillons', 'path' => '/admin/projects'],
                ['label' => 'Blog', 'total' => $posts->count(), 'detail' => $publishedPostsCount . ' publi?s, ' . $draftPostsCount . ' brouillons', 'path' => '/admin/blog'],
                ['label' => 'Comp?tences', 'total' => $skills->count(), 'detail' => $activeSkillsCount . ' actives actuellement', 'path' => '/admin/skills'],
                ['label' => 'Certifications', 'total' => $certifications->count(), 'detail' => $activeCertificationsCount . ' actives ou valides', 'path' => '/admin/certifications'],
                ['label' => 'Collaborations', 'total' => $collaborationCount, 'detail' => 'Membres reli?s aux projets', 'path' => '/admin/collaborations'],
                ['label' => 'Chatbot', 'total' => $chatbotKnowledge->count(), 'detail' => $knowledgeCount . ' fiches actives dans la base locale', 'path' => '/admin/chatbot'],
            ],
            'systemHealth' => SystemHealthService::dashboardReport(),
            'messageAlertDays' => $messageAlertDays,
        ], 'admin');
    }
}
