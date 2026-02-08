<?php
/**
 * Parser - Parse input pesan pengeluaran
 */

class Parser
{
    /**
     * Parse pesan pengeluaran: "makan 50000 warteg"
     * Format: <kategori> <nominal> [deskripsi]
     */
    public function parseExpense(string $message): ?array
    {
        $message = trim($message);
        
        // Pattern: kata nominal [deskripsi opsional]
        // Contoh: makan 50000 warteg
        //         transport 25000
        //         belanja 100rb indomaret
        
        $pattern = '/^(\w+)\s+([\d.,]+(?:rb|ribu|k|jt|juta)?)\s*(.*)$/i';
        
        if (!preg_match($pattern, $message, $matches)) {
            return null;
        }

        $category = strtolower($matches[1]);
        $amountStr = $matches[2];
        $description = trim($matches[3]) ?: null;

        // Parse nominal
        $amount = $this->parseAmount($amountStr);

        if ($amount <= 0) {
            return null;
        }

        return [
            'category' => $category,
            'amount' => $amount,
            'description' => $description
        ];
    }

    /**
     * Parse nominal dengan berbagai format
     * Contoh: 50000, 50.000, 50,000, 50rb, 50ribu, 50k, 2jt, 2juta
     * Support desimal: 1.5jt, 2,5rb, 1.5k
     */
    public function parseAmount(string $amountStr): float
    {
        $amountStr = strtolower(trim($amountStr));
        
        // Handle suffix dengan desimal: 1.5jt, 2,5rb, 1.5k
        $multiplier = 1;
        
        // Pattern untuk angka dengan desimal + suffix (1.5jt, 2,5rb, 1.5k)
        if (preg_match('/^([\d]+[.,][\d]+)(rb|ribu|k)$/i', $amountStr, $matches)) {
            // Ganti koma jadi titik untuk float parsing
            $amountStr = str_replace(',', '.', $matches[1]);
            $multiplier = 1000;
            return (float) $amountStr * $multiplier;
        } elseif (preg_match('/^([\d]+[.,][\d]+)(jt|juta)$/i', $amountStr, $matches)) {
            $amountStr = str_replace(',', '.', $matches[1]);
            $multiplier = 1000000;
            return (float) $amountStr * $multiplier;
        }
        
        // Pattern untuk angka bulat + suffix (50rb, 2jt, 100k)
        if (preg_match('/^(\d+)(rb|ribu|k)$/i', $amountStr, $matches)) {
            return (float) $matches[1] * 1000;
        } elseif (preg_match('/^(\d+)(jt|juta)$/i', $amountStr, $matches)) {
            return (float) $matches[1] * 1000000;
        }
        
        // Angka biasa dengan separator ribuan (50.000, 1.500.000, 50,000)
        // Deteksi apakah titik/koma adalah separator ribuan atau desimal
        // Jika ada 3 digit setelah titik/koma terakhir, itu separator ribuan
        if (preg_match('/[.,]\d{3}$/', $amountStr)) {
            // Separator ribuan - hapus semua titik dan koma
            $amountStr = str_replace(['.', ','], '', $amountStr);
        } else {
            // Mungkin desimal - ganti koma terakhir jadi titik
            $amountStr = preg_replace('/,(\d{1,2})$/', '.$1', $amountStr);
            // Hapus separator ribuan yang tersisa
            $amountStr = str_replace(',', '', $amountStr);
            // Jika masih ada titik ganda, yang pertama adalah separator ribuan
            if (substr_count($amountStr, '.') > 1) {
                $lastDot = strrpos($amountStr, '.');
                $beforeLast = substr($amountStr, 0, $lastDot);
                $afterLast = substr($amountStr, $lastDot);
                $amountStr = str_replace('.', '', $beforeLast) . $afterLast;
            }
        }

        $amount = (float) preg_replace('/[^\d.]/', '', $amountStr);
        
        return $amount * $multiplier;
    }

    /**
     * Parse command dengan argumen
     * Contoh: "tambah kategori kopi" -> ['command' => 'tambah kategori', 'args' => 'kopi']
     */
    public function parseCommand(string $message): array
    {
        $message = strtolower(trim($message));
        
        // Daftar command yang didukung
        // PENTING: Urutan dari paling spesifik ke paling umum (karena strpos matching)
        $commands = [
            'tambah kategori' => 'add_category',
            'hapus terakhir' => 'delete_last',
            'report mingguan' => 'report_weekly',
            'report bulanan' => 'report_monthly',
            'report tahunan' => 'report_yearly',
            'laporan mingguan' => 'report_weekly',
            'laporan bulanan' => 'report_monthly',
            'laporan tahunan' => 'report_yearly',
            'report' => 'report_custom',     // catch-all: report 2025, report januari 2025, dll
            'laporan' => 'report_custom',    // catch-all: laporan 2025, laporan januari 2025, dll
            'total hari ini' => 'total_today',
            'total minggu ini' => 'total_week',
            'total bulan ini' => 'total_month',
            'total tahun ini' => 'total_year',
            'total' => 'total_custom',       // catch-all: total 2025, total januari 2025, dll
            'kategori' => 'list_categories',
            'bantuan' => 'help',
            'help' => 'help',
            'ringkasan hari ini' => 'summary_today',
            'ringkasan minggu ini' => 'summary_week',
            'ringkasan bulan ini' => 'summary_month',
            'ringkasan tahun ini' => 'summary_year',
            'ringkasan' => 'summary_custom', // catch-all: ringkasan 2025, ringkasan januari 2025, dll
            'riwayat' => 'history',
            'history' => 'history',
            'perbandingan minggu' => 'compare_week',
            'perbandingan bulan' => 'compare_month',
            'perbandingan' => 'compare_custom', // catch-all: perbandingan januari 2025, dll
        ];

        foreach ($commands as $pattern => $command) {
            if (strpos($message, $pattern) === 0) {
                $args = trim(substr($message, strlen($pattern)));
                return [
                    'command' => $command,
                    'args' => $args ?: null
                ];
            }
        }

        return [
            'command' => null,
            'args' => null
        ];
    }

    /**
     * Parse multiple expenses dalam satu pesan
     * Format: "makan 50rb + transport 25rb gojek"
     * @param string $message
     * @return array Array of expense data atau empty array
     */
    public function parseMultipleExpenses(string $message): array
    {
        $message = trim($message);
        
        // Split by + atau &
        $parts = preg_split('/\s*[+&]\s*/', $message);
        
        if (count($parts) <= 1) {
            // Bukan multiple expense
            return [];
        }
        
        $expenses = [];
        foreach ($parts as $part) {
            $expense = $this->parseExpense(trim($part));
            if ($expense) {
                $expenses[] = $expense;
            }
        }
        
        // Return expenses hanya jika semua parts berhasil di-parse
        // Atau minimal 2 expense berhasil
        if (count($expenses) >= 2) {
            return $expenses;
        }
        
        return [];
    }

    /**
     * Cek apakah message adalah multiple expense
     * @param string $message
     * @return bool
     */
    public function isMultipleExpense(string $message): bool
    {
        return !empty($this->parseMultipleExpenses($message));
    }

    /**
     * Parse multi-date expenses format (ddmmyy)
     * Format: 250226 followed by expenses on separate lines
     * Example:
     * 250226
     * makan 7k nasduk
     * belanja 100k alfa
     * 260226
     * makan 5k nasi
     * 
     * @param string $message
     * @return array|null Array of ['date' => 'YYYY-MM-DD', 'expenses' => [...]] or null
     */
    public function parseMultiDateExpenses(string $message): ?array
    {
        $message = trim($message);
        $lines = preg_split('/\r\n|\r|\n/', $message);
        
        if (empty($lines)) {
            return null;
        }
        
        $result = [];
        $currentDate = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Check if line is a date in ddmmyy format (6 digits)
            if (preg_match('/^(\d{2})(\d{2})(\d{2})$/', $line, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = '20' . $matches[3]; // Assume 20xx
                
                if (checkdate((int)$month, (int)$day, (int)$year)) {
                    $currentDate = "{$year}-{$month}-{$day}";
                    if (!isset($result[$currentDate])) {
                        $result[$currentDate] = [];
                    }
                }
                continue;
            }
            
            // If we have a current date, try to parse as expense
            if ($currentDate !== null) {
                $expense = $this->parseExpense($line);
                if ($expense) {
                    $result[$currentDate][] = $expense;
                }
            }
        }
        
        // Filter out dates with no expenses
        $result = array_filter($result, function($expenses) {
            return !empty($expenses);
        });
        
        return !empty($result) ? $result : null;
    }

    /**
     * Parse custom period dari argumen
     * Contoh: "2025", "januari 2025", "jan 2025", "2024-2025",
     *         "januari 2024 hingga juni 2025", "01/01/2024 hingga 31/12/2025"
     * @param string|null $args
     * @return array|null
     */
    public function parseCustomPeriod(?string $args): ?array
    {
        if ($args === null || trim($args) === '') {
            return null;
        }

        $args = strtolower(trim($args));

        // Check for range keywords (hingga, sampai)
        if (preg_match('/\s+(hingga|sampai)\s+/i', $args)) {
            return $this->parsePeriodRange($args);
        }

        // Check for year range with dash (2024-2025) - must be 4-digit years
        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $args, $matches)) {
            $startYear = (int)$matches[1];
            $endYear = (int)$matches[2];

            if ($startYear > $endYear) {
                return null;
            }

            return [
                'type' => 'year_range',
                'start_year' => $startYear,
                'end_year' => $endYear
            ];
        }

        // Check for year only (2025)
        if (preg_match('/^(\d{4})$/', $args, $matches)) {
            return [
                'type' => 'year',
                'year' => (int)$matches[1]
            ];
        }

        // Check for month and year (januari 2025 / jan 2025)
        if (preg_match('/^(\w+)\s+(\d{4})$/', $args, $matches)) {
            $month = $this->parseMonthName($matches[1]);
            if ($month !== null) {
                return [
                    'type' => 'month',
                    'year' => (int)$matches[2],
                    'month' => $month
                ];
            }
        }

        return null;
    }

    /**
     * Parse period range
     * Contoh: "januari 2024 hingga juni 2025", "01/01/2024 hingga 31/12/2025"
     * @param string $args
     * @return array|null
     */
    private function parsePeriodRange(string $args): ?array
    {
        // Split by range keywords
        $parts = preg_split('/\s+(hingga|sampai)\s+/i', $args, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) !== 2) {
            return null;
        }

        $startPart = trim($parts[0]);
        $endPart = trim($parts[1]);

        // Try to parse as month/year range (januari 2024 hingga juni 2025)
        if (preg_match('/^(\w+)\s+(\d{4})$/', $startPart, $startMatches) &&
            preg_match('/^(\w+)\s+(\d{4})$/', $endPart, $endMatches)) {

            $startMonth = $this->parseMonthName($startMatches[1]);
            $endMonth = $this->parseMonthName($endMatches[1]);

            if ($startMonth !== null && $endMonth !== null) {
                $startYear = (int)$startMatches[2];
                $endYear = (int)$endMatches[2];
                $lastDay = (int)date('t', mktime(0, 0, 0, $endMonth, 1, $endYear));

                $startDate = sprintf('%04d-%02d-01', $startYear, $startMonth);
                $endDate = sprintf('%04d-%02d-%02d', $endYear, $endMonth, $lastDay);

                if ($startDate > $endDate) {
                    return null;
                }

                return [
                    'type' => 'month_range',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_label' => $this->getMonthName($startMonth) . ' ' . $startYear,
                    'end_label' => $this->getMonthName($endMonth) . ' ' . $endYear
                ];
            }
        }

        // Try to parse as date range (DD/MM/YYYY or YYYY-MM-DD)
        $startDate = $this->parseDate($startPart);
        $endDate = $this->parseDate($endPart);

        if ($startDate !== null && $endDate !== null) {
            if ($startDate > $endDate) {
                return null;
            }

            return [
                'type' => 'date_range',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_label' => date('d/m/Y', strtotime($startDate)),
                'end_label' => date('d/m/Y', strtotime($endDate))
            ];
        }

        return null;
    }

    /**
     * Parse tanggal dalam format DD/MM/YYYY atau YYYY-MM-DD
     * @param string $dateStr
     * @return string|null Format YYYY-MM-DD atau null jika invalid
     */
    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);

        // Format: DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];

            if (!checkdate($month, $day, $year)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        // Format: YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateStr, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];

            if (!checkdate($month, $day, $year)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return null;
    }

    /**
     * Parse nama bulan (full atau short) ke nomor (1-12)
     * @param string $monthName
     * @return int|null
     */
    private function parseMonthName(string $monthName): ?int
    {
        $monthName = strtolower(trim($monthName));

        $months = [
            'januari' => 1, 'jan' => 1,
            'februari' => 2, 'feb' => 2,
            'maret' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5,
            'juni' => 6, 'jun' => 6,
            'juli' => 7, 'jul' => 7,
            'agustus' => 8, 'agu' => 8, 'ags' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12,
        ];

        return $months[$monthName] ?? null;
    }

    /**
     * Get nama bulan dari nomor (1-12)
     * @param int $month
     * @return string
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$month] ?? '';
    }
}
