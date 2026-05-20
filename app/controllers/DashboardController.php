<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Activity;
use App\Models\Analytics;
use App\Models\Certification;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Project;
use App\Services\NotificationService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        NotificationService::syncCertificationAlerts();

        $analytics = new Analytics();
        $messageAlertDays = max(1, (int) env('DASHBOARD_UNREAD_ALERT_DAYS', 3));
        $oldUnreadMessages = Database::query('SELECT * FROM contacts WHERE statut = \'nouveau\' AND created_at <= DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY created_at ASC', [$messageAlertDays])->fetchAll();

        $this->view('admin/dashboard', [
            'counts' => [
                'visitors_month' => $analytics->monthVisitors(),
                'published_projects' => (new Project())->count('statut = \'publie\''),
                'unread_messages' => (new Contact())->unreadCount(),
                'active_certifications' => (new Certification())->count('est_active = 1 AND (date_expiration IS NULL OR date_expiration >= CURDATE())'),
            ],
            'timeline' => $analytics->timeline(30),
            'expiringCertifications' => (new Certification())->expiringSoon(30),
            'oldUnreadMessages' => $oldUnreadMessages,
            'notifications' => array_slice((new Notification())->all('created_at DESC'), 0, 6),
            'activities' => array_slice((new Activity())->all('created_at DESC'), 0, 10),
            'analyticsSummary' => $analytics->summary(30),
            'messageAlertDays' => $messageAlertDays,
        ], 'admin');
    }
}
