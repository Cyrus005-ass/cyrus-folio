<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Analytics;

class AnalyticsController extends Controller
{
    public function adminIndex(): void
    {
        $this->requireAdmin();
        $period = $this->period();
        $analytics = new Analytics();

        $this->view('admin/analytics', [
            'period' => $period,
            'summary' => $analytics->summary($period),
            'timeline' => $analytics->timeline($period),
            'pages' => $analytics->pages($period),
            'devices' => $analytics->devices($period),
            'countries' => $analytics->countries($period),
        ], 'admin');
    }

    public function summaryApi(): void
    {
        $this->requireAdmin();
        $period = $this->period();
        $analytics = new Analytics();
        $this->json(['success' => true, 'data' => [
            'period' => $period,
            'summary' => $analytics->summary($period),
            'timeline' => $analytics->timeline($period),
            'pages' => $analytics->pages($period),
            'devices' => $analytics->devices($period),
            'countries' => $analytics->countries($period),
        ]]);
    }

    public function pagesApi(): void
    {
        $this->requireAdmin();
        $this->json(['success' => true, 'data' => (new Analytics())->pages($this->period())]);
    }

    private function period(): int
    {
        $requested = (int) ($_GET['period'] ?? 30);
        return in_array($requested, [7, 30, 90], true) ? $requested : 30;
    }
}
