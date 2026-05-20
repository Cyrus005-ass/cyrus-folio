<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Analytics extends Model
{
    protected string $table = 'analytics';
    protected array $fillable = ['session_id', 'page', 'referrer', 'ip_address', 'user_agent', 'device', 'country', 'country_code'];

    public function summary(int $days = 30): array
    {
        [$where, $params] = $this->whereSince($days);
        $totalVisits = (int) Database::query('SELECT COUNT(*) total FROM analytics WHERE ' . $where, $params)->fetch()['total'];
        $uniqueVisitors = (int) Database::query('SELECT COUNT(DISTINCT session_id) total FROM analytics WHERE ' . $where, $params)->fetch()['total'];
        $avgDuration = (int) Database::query('SELECT COALESCE(AVG(duration), 0) avg_duration FROM (SELECT TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) duration FROM analytics WHERE ' . $where . ' GROUP BY session_id) sessions', $params)->fetch()['avg_duration'];
        $bounceRate = (float) Database::query('SELECT COALESCE(AVG(is_bounce) * 100, 0) bounce_rate FROM (SELECT CASE WHEN COUNT(*) = 1 THEN 1 ELSE 0 END is_bounce FROM analytics WHERE ' . $where . ' GROUP BY session_id) sessions', $params)->fetch()['bounce_rate'];

        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'avg_session_duration' => $avgDuration,
            'bounce_rate' => round($bounceRate, 2),
        ];
    }

    public function monthVisitors(): int
    {
        return (int) Database::query('SELECT COUNT(DISTINCT session_id) total FROM analytics WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())')->fetch()['total'];
    }

    public function timeline(int $days = 30): array
    {
        [$where, $params] = $this->whereSince($days);
        $rows = Database::query('SELECT DATE(created_at) day, COUNT(*) visits, COUNT(DISTINCT session_id) visitors FROM analytics WHERE ' . $where . ' GROUP BY DATE(created_at) ORDER BY day ASC', $params)->fetchAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['day']] = ['day' => $row['day'], 'visits' => (int) $row['visits'], 'visitors' => (int) $row['visitors']];
        }

        $timeline = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = date('Y-m-d', strtotime('-' . $offset . ' days'));
            $timeline[] = $indexed[$day] ?? ['day' => $day, 'visits' => 0, 'visitors' => 0];
        }

        return $timeline;
    }

    public function pages(int $days = 30, int $limit = 10): array
    {
        [$where, $params] = $this->whereSince($days);
        $total = max(1, $this->summary($days)['total_visits']);
        $rows = Database::query('SELECT page, COUNT(*) total FROM analytics WHERE ' . $where . ' GROUP BY page ORDER BY total DESC LIMIT ' . (int) $limit, $params)->fetchAll();

        return array_map(fn ($row) => ['page' => $row['page'], 'total' => (int) $row['total'], 'percentage' => round(((int) $row['total'] / $total) * 100, 2)], $rows);
    }

    public function devices(int $days = 30): array
    {
        [$where, $params] = $this->whereSince($days);
        $rows = Database::query('SELECT device, COUNT(*) total FROM analytics WHERE ' . $where . ' GROUP BY device ORDER BY total DESC', $params)->fetchAll();
        return array_map(fn ($row) => ['device' => $row['device'], 'total' => (int) $row['total']], $rows);
    }

    public function countries(int $days = 30, int $limit = 10): array
    {
        [$where, $params] = $this->whereSince($days);
        $rows = Database::query('SELECT COALESCE(country, \'Inconnu\') country, COALESCE(country_code, \'XX\') country_code, COUNT(*) total FROM analytics WHERE ' . $where . ' GROUP BY COALESCE(country, \'Inconnu\'), COALESCE(country_code, \'XX\') ORDER BY total DESC LIMIT ' . (int) $limit, $params)->fetchAll();
        return array_map(fn ($row) => ['country' => $row['country'], 'country_code' => $row['country_code'], 'total' => (int) $row['total']], $rows);
    }

    private function whereSince(int $days): array
    {
        return ['created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)', [max(0, $days - 1)]];
    }
}
