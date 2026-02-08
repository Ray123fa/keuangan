<?php
/**
 * ExpenseService - CRUD operasi untuk pengeluaran
 */

require_once __DIR__ . '/../database.php';

class ExpenseService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Tambah pengeluaran baru
     */
    public function addExpense(string $categoryName, float $amount, ?string $description = null): array
    {
        // Cari atau buat kategori
        $category = $this->getCategoryByName($categoryName);
        
        if (!$category) {
            return [
                'success' => false,
                'message' => "Kategori '$categoryName' tidak ditemukan. Ketik 'kategori' untuk lihat daftar kategori atau 'tambah kategori $categoryName' untuk menambahkan."
            ];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO expenses (category_id, amount, description) VALUES (?, ?, ?)'
        );
        $stmt->execute([$category['id'], $amount, $description]);

        return [
            'success' => true,
            'message' => sprintf(
                "Tercatat: %s Rp%s%s",
                ucfirst($categoryName),
                number_format($amount, 0, ',', '.'),
                $description ? " ($description)" : ""
            ),
            'expense_id' => $this->db->lastInsertId()
        ];
    }

    /**
     * Hapus pengeluaran terakhir
     */
    public function deleteLastExpense(): array
    {
        // Ambil pengeluaran terakhir
        $stmt = $this->db->query(
            'SELECT e.*, c.name as category_name 
             FROM expenses e 
             JOIN categories c ON e.category_id = c.id 
             ORDER BY e.id DESC LIMIT 1'
        );
        $expense = $stmt->fetch();

        if (!$expense) {
            return [
                'success' => false,
                'message' => 'Tidak ada pengeluaran yang bisa dihapus.'
            ];
        }

        // Hapus
        $stmt = $this->db->prepare('DELETE FROM expenses WHERE id = ?');
        $stmt->execute([$expense['id']]);

        return [
            'success' => true,
            'message' => sprintf(
                "Dihapus: %s Rp%s%s",
                ucfirst($expense['category_name']),
                number_format($expense['amount'], 0, ',', '.'),
                $expense['description'] ? " ({$expense['description']})" : ""
            )
        ];
    }

    /**
     * Ambil total pengeluaran berdasarkan periode
     */
    public function getTotal(string $period): array
    {
        $where = $this->getPeriodWhere($period);
        
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE $where";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        $periodLabel = $this->getPeriodLabel($period);

        return [
            'success' => true,
            'total' => (float) $result['total'],
            'message' => sprintf(
                "Total pengeluaran %s: Rp%s",
                $periodLabel,
                number_format($result['total'], 0, ',', '.')
            )
        ];
    }

    /**
     * Ambil total per kategori berdasarkan periode
     */
    public function getTotalByCategory(string $period): array
    {
        $where = $this->getPeriodWhere($period, 'e');

        $sql = "SELECT c.name, COALESCE(SUM(e.amount), 0) as total 
                FROM categories c 
                LEFT JOIN expenses e ON c.id = e.category_id AND $where
                GROUP BY c.id, c.name
                HAVING total > 0
                ORDER BY total DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua pengeluaran berdasarkan periode
     */
    public function getExpenses(string $period): array
    {
        $where = $this->getPeriodWhere($period, 'e');

        $sql = "SELECT e.*, c.name as category_name 
                FROM expenses e 
                JOIN categories c ON e.category_id = c.id 
                WHERE $where
                ORDER BY e.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil kategori berdasarkan nama
     */
    public function getCategoryByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE LOWER(name) = LOWER(?)');
        $stmt->execute([trim($name)]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Ambil semua kategori
     */
    public function getAllCategories(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY is_custom, name');
        return $stmt->fetchAll();
    }

    /**
     * Tambah kategori baru
     */
    public function addCategory(string $name): array
    {
        $name = strtolower(trim($name));
        
        // Cek apakah sudah ada
        if ($this->getCategoryByName($name)) {
            return [
                'success' => false,
                'message' => "Kategori '$name' sudah ada."
            ];
        }

        $stmt = $this->db->prepare('INSERT INTO categories (name, is_custom) VALUES (?, 1)');
        $stmt->execute([$name]);

        return [
            'success' => true,
            'message' => "Kategori '$name' berhasil ditambahkan."
        ];
    }

    /**
     * Generate WHERE clause berdasarkan periode
     * @param string $tableAlias Alias table (default 'e' untuk expenses, kosong untuk query tanpa join)
     */
    private function getPeriodWhere(string $period, string $tableAlias = ''): string
    {
        $prefix = $tableAlias ? "{$tableAlias}." : '';
        
        switch ($period) {
            case 'hari':
            case 'today':
                return "DATE({$prefix}created_at) = CURDATE()";
            
            case 'minggu':
            case 'mingguan':
            case 'week':
                return "YEARWEEK({$prefix}created_at, 1) = YEARWEEK(CURDATE(), 1)";
            
            case 'bulan':
            case 'bulanan':
            case 'month':
                return "YEAR({$prefix}created_at) = YEAR(CURDATE()) AND MONTH({$prefix}created_at) = MONTH(CURDATE())";
            
            case 'tahun':
            case 'tahunan':
            case 'year':
                return "YEAR({$prefix}created_at) = YEAR(CURDATE())";
            
            default:
                return "1=1";
        }
    }

    /**
     * Get label periode untuk pesan
     */
    private function getPeriodLabel(string $period): string
    {
        switch ($period) {
            case 'hari':
            case 'today':
                return 'hari ini';
            case 'minggu':
            case 'mingguan':
            case 'week':
                return 'minggu ini';
            case 'bulan':
            case 'bulanan':
            case 'month':
                return 'bulan ini';
            case 'tahun':
            case 'tahunan':
            case 'year':
                return 'tahun ini';
            default:
                return $period;
        }
    }

    /**
     * Ambil total hari ini (untuk quick stats)
     */
    public function getTodayTotal(): float
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return (float) $result['total'];
    }

    /**
     * Ambil riwayat transaksi terakhir
     * @param int $limit Jumlah transaksi
     * @return array
     */
    public function getHistory(int $limit = 5): array
    {
        $sql = "SELECT e.*, c.name as category_name 
                FROM expenses e 
                JOIN categories c ON e.category_id = c.id 
                ORDER BY e.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Format riwayat untuk pesan
     * @param int $limit
     * @return string
     */
    public function formatHistory(int $limit = 5): string
    {
        $expenses = $this->getHistory($limit);
        
        if (empty($expenses)) {
            return "Belum ada transaksi.";
        }

        $lines = ["{$limit} TRANSAKSI TERAKHIR"];
        $now = new DateTime();
        
        foreach ($expenses as $i => $exp) {
            $num = $i + 1;
            $date = new DateTime($exp['created_at']);
            $diff = $now->diff($date);
            
            // Format waktu
            if ($diff->days === 0) {
                $timeStr = "Hari ini " . $date->format('H:i');
            } elseif ($diff->days === 1) {
                $timeStr = "Kemarin " . $date->format('H:i');
            } elseif ($diff->days < 7) {
                $timeStr = "{$diff->days} hari lalu";
            } else {
                $timeStr = $date->format('d/m/Y');
            }
            
            $desc = $exp['description'] ? " ({$exp['description']})" : '';
            $lines[] = sprintf(
                "%d. %s - %s Rp%s%s",
                $num,
                $timeStr,
                ucfirst($exp['category_name']),
                number_format($exp['amount'], 0, ',', '.'),
                $desc
            );
        }
        
        return implode("\n", $lines);
    }

    /**
     * Generate ringkasan text untuk periode tertentu
     * @param string $period
     * @return string
     */
    public function getSummary(string $period): string
    {
        $totalResult = $this->getTotal($period);
        $byCategory = $this->getTotalByCategory($period);
        $expenses = $this->getExpenses($period);
        $periodLabel = strtoupper($this->getPeriodLabel($period));
        
        if (empty($expenses)) {
            return "Tidak ada pengeluaran untuk " . $this->getPeriodLabel($period) . ".";
        }

        $lines = ["RINGKASAN {$periodLabel}"];
        $lines[] = sprintf("Total: Rp%s", number_format($totalResult['total'], 0, ',', '.'));
        $lines[] = "";
        $lines[] = "Per kategori:";
        
        $grandTotal = $totalResult['total'];
        foreach ($byCategory as $item) {
            $percentage = $grandTotal > 0 ? round(($item['total'] / $grandTotal) * 100) : 0;
            $lines[] = sprintf(
                "- %s: Rp%s (%d%%)",
                ucfirst($item['name']),
                number_format($item['total'], 0, ',', '.'),
                $percentage
            );
        }
        
        $lines[] = "";
        $lines[] = "Transaksi: " . count($expenses) . " kali";
        
        return implode("\n", $lines);
    }

    /**
     * Get total untuk periode sebelumnya (untuk perbandingan)
     * @param string $period 'minggu' atau 'bulan'
     * @return float
     */
    public function getPreviousPeriodTotal(string $period): float
    {
        switch ($period) {
            case 'minggu':
            case 'mingguan':
            case 'week':
                $where = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)";
                break;
            case 'bulan':
            case 'bulanan':
            case 'month':
                $where = "YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)";
                break;
            default:
                return 0;
        }
        
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE $where";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return (float) $result['total'];
    }

    /**
     * Generate perbandingan dengan periode sebelumnya
     * @param string $period 'minggu' atau 'bulan'
     * @return string
     */
    public function getComparison(string $period): string
    {
        $currentTotal = $this->getTotal($period)['total'];
        $previousTotal = $this->getPreviousPeriodTotal($period);
        $byCategory = $this->getTotalByCategory($period);
        
        $periodLabel = $this->getPeriodLabel($period);
        $prevLabel = $period === 'minggu' || $period === 'mingguan' || $period === 'week' 
            ? 'minggu lalu' 
            : 'bulan lalu';
        
        $lines = [sprintf("Total pengeluaran %s: Rp%s", $periodLabel, number_format($currentTotal, 0, ',', '.'))];
        
        // Perbandingan
        if ($previousTotal > 0) {
            $diff = $currentTotal - $previousTotal;
            $percentage = round(($diff / $previousTotal) * 100, 1);
            $sign = $diff >= 0 ? '+' : '';
            $lines[] = sprintf(
                "vs %s: Rp%s (%s%s%%)",
                $prevLabel,
                number_format($previousTotal, 0, ',', '.'),
                $sign,
                $percentage
            );
        } else {
            $lines[] = sprintf("vs %s: Rp0 (tidak ada data)", $prevLabel);
        }
        
        // Detail per kategori
        if (!empty($byCategory)) {
            $lines[] = "";
            $lines[] = "Per kategori:";
            foreach ($byCategory as $item) {
                $lines[] = sprintf(
                    "- %s: Rp%s",
                    ucfirst($item['name']),
                    number_format($item['total'], 0, ',', '.')
                );
            }
        }
        
        return implode("\n", $lines);
    }

    // ================================================================
    // CUSTOM PERIOD METHODS
    // ================================================================

    /**
     * Generate WHERE clause untuk custom period
     * @param array $period Parsed period dari Parser::parseCustomPeriod()
     * @param string $tableAlias Alias table
     * @return string WHERE clause (tanpa keyword WHERE)
     */
    public function getCustomPeriodWhere(array $period, string $tableAlias = ''): string
    {
        $prefix = $tableAlias ? "{$tableAlias}." : '';

        switch ($period['type']) {
            case 'year':
                $year = (int)$period['year'];
                return "YEAR({$prefix}created_at) = {$year}";

            case 'month':
                $year = (int)$period['year'];
                $month = (int)$period['month'];
                return "YEAR({$prefix}created_at) = {$year} AND MONTH({$prefix}created_at) = {$month}";

            case 'year_range':
                $startYear = (int)$period['start_year'];
                $endYear = (int)$period['end_year'];
                return "YEAR({$prefix}created_at) BETWEEN {$startYear} AND {$endYear}";

            case 'month_range':
            case 'date_range':
                $startQuoted = $this->db->quote($period['start_date']);
                $endQuoted = $this->db->quote($period['end_date'] . ' 23:59:59');
                return "{$prefix}created_at BETWEEN {$startQuoted} AND {$endQuoted}";

            default:
                return "1=1";
        }
    }

    /**
     * Get label untuk custom period
     * @param array $period
     * @return string
     */
    public function getCustomPeriodLabel(array $period): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        switch ($period['type']) {
            case 'year':
                return "Tahun {$period['year']}";

            case 'month':
                return $months[$period['month']] . " {$period['year']}";

            case 'year_range':
                return "{$period['start_year']} - {$period['end_year']}";

            case 'month_range':
                return "{$period['start_label']} - {$period['end_label']}";

            case 'date_range':
                return "{$period['start_label']} - {$period['end_label']}";

            default:
                return 'Custom';
        }
    }

    /**
     * Ambil total pengeluaran untuk custom period
     * @param array $period
     * @return array
     */
    public function getTotalCustom(array $period): array
    {
        $where = $this->getCustomPeriodWhere($period);
        $label = $this->getCustomPeriodLabel($period);

        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE $where";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'success' => true,
            'total' => (float) $result['total'],
            'message' => sprintf(
                "Total pengeluaran %s: Rp%s",
                $label,
                number_format($result['total'], 0, ',', '.')
            )
        ];
    }

    /**
     * Ambil total per kategori untuk custom period
     * @param array $period
     * @return array
     */
    public function getTotalByCategoryCustom(array $period): array
    {
        $where = $this->getCustomPeriodWhere($period, 'e');

        $sql = "SELECT c.name, COALESCE(SUM(e.amount), 0) as total 
                FROM categories c 
                LEFT JOIN expenses e ON c.id = e.category_id AND $where
                GROUP BY c.id, c.name
                HAVING total > 0
                ORDER BY total DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua pengeluaran untuk custom period
     * @param array $period
     * @return array
     */
    public function getExpensesCustom(array $period): array
    {
        $where = $this->getCustomPeriodWhere($period, 'e');

        $sql = "SELECT e.*, c.name as category_name 
                FROM expenses e 
                JOIN categories c ON e.category_id = c.id 
                WHERE $where
                ORDER BY e.created_at DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Generate ringkasan text untuk custom period
     * @param array $period
     * @return string
     */
    public function getSummaryCustom(array $period): string
    {
        $totalResult = $this->getTotalCustom($period);
        $byCategory = $this->getTotalByCategoryCustom($period);
        $expenses = $this->getExpensesCustom($period);
        $periodLabel = strtoupper($this->getCustomPeriodLabel($period));

        if (empty($expenses)) {
            return "Tidak ada pengeluaran untuk " . $this->getCustomPeriodLabel($period) . ".";
        }

        $lines = ["RINGKASAN {$periodLabel}"];
        $lines[] = sprintf("Total: Rp%s", number_format($totalResult['total'], 0, ',', '.'));
        $lines[] = "";
        $lines[] = "Per kategori:";

        $grandTotal = $totalResult['total'];
        foreach ($byCategory as $item) {
            $percentage = $grandTotal > 0 ? round(($item['total'] / $grandTotal) * 100) : 0;
            $lines[] = sprintf(
                "- %s: Rp%s (%d%%)",
                ucfirst($item['name']),
                number_format($item['total'], 0, ',', '.'),
                $percentage
            );
        }

        $lines[] = "";
        $lines[] = "Transaksi: " . count($expenses) . " kali";

        return implode("\n", $lines);
    }

    /**
     * Get total untuk periode sebelumnya dari custom period (untuk perbandingan)
     * @param array $period Hanya support type 'month' dan 'year'
     * @return float
     */
    public function getPreviousPeriodTotalCustom(array $period): float
    {
        switch ($period['type']) {
            case 'month':
                // Previous month
                $prevMonth = $period['month'] - 1;
                $prevYear = $period['year'];
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear--;
                }
                $where = "YEAR(created_at) = {$prevYear} AND MONTH(created_at) = {$prevMonth}";
                break;

            case 'year':
                $prevYear = $period['year'] - 1;
                $where = "YEAR(created_at) = {$prevYear}";
                break;

            default:
                return 0;
        }

        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE $where";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return (float) $result['total'];
    }

    /**
     * Generate perbandingan untuk custom period
     * @param array $period Hanya support type 'month' dan 'year'
     * @return string
     */
    public function getComparisonCustom(array $period): string
    {
        $currentTotal = $this->getTotalCustom($period)['total'];
        $previousTotal = $this->getPreviousPeriodTotalCustom($period);
        $byCategory = $this->getTotalByCategoryCustom($period);
        $periodLabel = $this->getCustomPeriodLabel($period);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // Generate previous period label
        if ($period['type'] === 'month') {
            $prevMonth = $period['month'] - 1;
            $prevYear = $period['year'];
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            $prevLabel = $months[$prevMonth] . " {$prevYear}";
        } elseif ($period['type'] === 'year') {
            $prevLabel = "Tahun " . ($period['year'] - 1);
        } else {
            $prevLabel = 'periode sebelumnya';
        }

        $lines = [sprintf("Total pengeluaran %s: Rp%s", $periodLabel, number_format($currentTotal, 0, ',', '.'))];

        // Perbandingan
        if ($previousTotal > 0) {
            $diff = $currentTotal - $previousTotal;
            $percentage = round(($diff / $previousTotal) * 100, 1);
            $sign = $diff >= 0 ? '+' : '';
            $lines[] = sprintf(
                "vs %s: Rp%s (%s%s%%)",
                $prevLabel,
                number_format($previousTotal, 0, ',', '.'),
                $sign,
                $percentage
            );
        } else {
            $lines[] = sprintf("vs %s: Rp0 (tidak ada data)", $prevLabel);
        }

        // Detail per kategori
        if (!empty($byCategory)) {
            $lines[] = "";
            $lines[] = "Per kategori:";
            foreach ($byCategory as $item) {
                $lines[] = sprintf(
                    "- %s: Rp%s",
                    ucfirst($item['name']),
                    number_format($item['total'], 0, ',', '.')
                );
            }
        }

        return implode("\n", $lines);
    }
}
