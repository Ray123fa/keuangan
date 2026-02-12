<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function getTotals(): array
    {
        $sql = 'SELECT
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount END), 0) as total_hari_ini,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN amount END), 0) as total_bulan_ini,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN amount END), 0) as total_tahun_ini
            FROM expenses';

        $row = $this->db->query($sql)->fetch();

        return [
            'today' => (int) ($row['total_hari_ini'] ?? 0),
            'month' => (int) ($row['total_bulan_ini'] ?? 0),
            'year' => (int) ($row['total_tahun_ini'] ?? 0),
        ];
    }

    public function getTopCategoriesThisMonth(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name, COALESCE(SUM(e.amount), 0) as total
             FROM expenses e
             JOIN categories c ON c.id = e.category_id
             WHERE YEAR(e.created_at) = YEAR(CURDATE())
               AND MONTH(e.created_at) = MONTH(CURDATE())
             GROUP BY c.id, c.name
             ORDER BY total DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row): array {
            $row['total'] = (int) ($row['total'] ?? 0);

            return $row;
        }, $rows);
    }

    public function getSpendingComparisons(): array
    {
        $sql = 'SELECT
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount END), 0) AS current_day,
            COALESCE(SUM(CASE WHEN DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN amount END), 0) AS previous_day,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN amount END), 0) AS current_year,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) - 1 THEN amount END), 0) AS previous_year,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN amount END), 0) AS current_month,
            COALESCE(SUM(CASE WHEN YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN amount END), 0) AS previous_month
            FROM expenses';

        $row = $this->db->query($sql)->fetch();

        $currentDay = (int) ($row['current_day'] ?? 0);
        $previousDay = (int) ($row['previous_day'] ?? 0);
        $currentYear = (int) ($row['current_year'] ?? 0);
        $previousYear = (int) ($row['previous_year'] ?? 0);
        $currentMonth = (int) ($row['current_month'] ?? 0);
        $previousMonth = (int) ($row['previous_month'] ?? 0);

        return [
            'daily' => $this->buildComparison($currentDay, $previousDay),
            'monthly' => $this->buildComparison($currentMonth, $previousMonth),
            'weekly' => $this->getWeeklySpendingComparison(),
            'yearly' => $this->buildComparison($currentYear, $previousYear),
        ];
    }

    public function getDailySeries(int $days = 7): array
    {
        $days = max(2, $days);

        $today = new \DateTimeImmutable('today');
        $rangeStart = $today->modify('-' . ($days - 1) . ' days');

        $stmt = $this->db->prepare(
            'SELECT DATE(created_at) AS day_date, COALESCE(SUM(amount), 0) AS total
             FROM expenses
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date
             GROUP BY day_date
             ORDER BY day_date ASC'
        );
        $stmt->execute([
            'start_date' => $rangeStart->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
        ]);

        $rows = $stmt->fetchAll() ?: [];
        $totalsByDate = [];
        foreach ($rows as $row) {
            $key = (string) ($row['day_date'] ?? '');
            if ($key === '') {
                continue;
            }

            $totalsByDate[$key] = (int) ($row['total'] ?? 0);
        }

        $labels = [];
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $rangeStart->modify('+' . $i . ' days');
            $dayKey = $day->format('Y-m-d');

            $labels[] = $day->format('d M');
            $data[] = $totalsByDate[$dayKey] ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $data,
            'window_label' => $days . ' hari terakhir',
        ];
    }

    public function getMonthlyComparisonSeries(): array
    {
        $sql = 'SELECT YEAR(created_at) AS year_number, MONTH(created_at) AS month_number, COALESCE(SUM(amount), 0) AS total
                FROM expenses
                WHERE YEAR(created_at) IN (YEAR(CURDATE()), YEAR(CURDATE()) - 1)
                GROUP BY year_number, month_number';

        $rows = $this->db->query($sql)->fetchAll() ?: [];

        $currentYear = (int) date('Y');
        $previousYear = $currentYear - 1;

        $currentSeries = array_fill(1, 12, 0);
        $previousSeries = array_fill(1, 12, 0);

        foreach ($rows as $row) {
            $year = (int) ($row['year_number'] ?? 0);
            $month = (int) ($row['month_number'] ?? 0);
            $total = (int) ($row['total'] ?? 0);

            if ($month < 1 || $month > 12) {
                continue;
            }

            if ($year === $currentYear) {
                $currentSeries[$month] = $total;
                continue;
            }

            if ($year === $previousYear) {
                $previousSeries[$month] = $total;
            }
        }

        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'current_year' => array_values($currentSeries),
            'previous_year' => array_values($previousSeries),
            'current_year_label' => (string) $currentYear,
            'previous_year_label' => (string) $previousYear,
        ];
    }

    public function getWeeklySeries(int $weeks = 8): array
    {
        $weeks = max(2, $weeks);

        $today = new \DateTimeImmutable('today');
        $dayOfWeek = (int) $today->format('N');
        $currentWeekStart = $today->modify('-' . ($dayOfWeek - 1) . ' days');
        $rangeStart = $currentWeekStart->modify('-' . ($weeks - 1) . ' weeks');
        $rangeEnd = $currentWeekStart->modify('+6 days');

        $stmt = $this->db->prepare(
            'SELECT DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY) AS week_start,
                    COALESCE(SUM(amount), 0) AS total
             FROM expenses
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date
             GROUP BY week_start
             ORDER BY week_start ASC'
        );
        $stmt->execute([
            'start_date' => $rangeStart->format('Y-m-d'),
            'end_date' => $rangeEnd->format('Y-m-d'),
        ]);

        $rows = $stmt->fetchAll() ?: [];
        $totalsByWeek = [];
        foreach ($rows as $row) {
            $key = (string) ($row['week_start'] ?? '');
            if ($key === '') {
                continue;
            }

            $totalsByWeek[$key] = (int) ($row['total'] ?? 0);
        }

        $labels = [];
        $data = [];
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $rangeStart->modify('+' . $i . ' weeks');
            $weekEnd = $weekStart->modify('+6 days');
            $weekKey = $weekStart->format('Y-m-d');

            $labels[] = $weekStart->format('d M') . ' - ' . $weekEnd->format('d M');
            $data[] = $totalsByWeek[$weekKey] ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $data,
            'window_label' => $weeks . ' minggu terakhir',
        ];
    }

    private function getWeeklySpendingComparison(): array
    {
        $sql = 'SELECT
            COALESCE(SUM(CASE WHEN DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND CURDATE() THEN amount END), 0) AS current_week,
            COALESCE(SUM(CASE WHEN DATE(created_at) BETWEEN DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY) AND DATE_SUB(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 1 DAY) THEN amount END), 0) AS previous_week
            FROM expenses';

        $row = $this->db->query($sql)->fetch();

        return $this->buildComparison(
            (int) ($row['current_week'] ?? 0),
            (int) ($row['previous_week'] ?? 0)
        );
    }

    private function buildComparison(int $currentValue, int $previousValue): array
    {
        $diff = $currentValue - $previousValue;
        $trend = $diff === 0 ? 'flat' : ($diff > 0 ? 'up' : 'down');

        if ($previousValue <= 0) {
            $percent = $currentValue > 0 ? null : 0.0;
        } else {
            $percent = ($diff / $previousValue) * 100;
        }

        return [
            'current' => $currentValue,
            'previous' => $previousValue,
            'diff' => $diff,
            'percent' => $percent,
            'trend' => $trend,
        ];
    }
}
