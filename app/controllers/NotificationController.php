<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAdmin();
        NotificationService::syncCertificationAlerts();
        $this->view('admin/notifications', ['notifications' => (new Notification())->all('created_at DESC')], 'admin');
    }

    public function indexApi(): void
    {
        $this->requireAdmin();
        $this->json(['success' => true, 'data' => (new Notification())->all('created_at DESC')]);
    }

    public function read(string $id): void
    {
        $this->requireAdmin();
        if (!is_api_request()) {
            $this->validateCsrf();
        }

        $model = new Notification();
        if (!$model->find($id)) {
            $this->fail('Notification introuvable.', '/admin/notifications', 404);
        }

        $model->markRead((int) $id);
        if (is_api_request()) {
            $this->json(['success' => true]);
        }

        flash('success', 'Notification lue.');
        redirect('/admin/notifications');
    }

    public function readAll(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        (new Notification())->markAllRead();
        flash('success', 'Toutes les notifications sont lues.');
        redirect('/admin/notifications');
    }

    public function open(string $id): void
    {
        $this->requireAdmin();
        $model = new Notification();
        $notification = $model->find($id);
        if (!$notification) {
            $this->fail('Notification introuvable.', '/admin/notifications', 404);
        }

        $model->markRead((int) $id);
        redirect($notification['lien'] ?? '/admin/notifications');
    }
}
