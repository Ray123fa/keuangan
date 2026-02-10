<?php
/**
 * MessageHandler - Handle dan route incoming messages dari WhatsApp
 */

require_once __DIR__ . '/../services/FonnteService.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../utils/Parser.php';

class MessageHandler
{
    private FonnteService $fonnte;
    private ExpenseService $expense;
    private ReportService $report;
    private Parser $parser;

    public function __construct()
    {
        $this->fonnte = new FonnteService();
        $this->expense = new ExpenseService();
        $this->report = new ReportService();
        $this->parser = new Parser();
    }

    /**
     * Handle incoming message
     */
    public function handle(string $sender, string $message): void
    {
        $message = trim($message);
        
        if (empty($message)) {
            return;
        }

        // Coba parse sebagai command dulu
        $command = $this->parser->parseCommand($message);

        if ($command['command']) {
            $this->handleCommand($sender, $command['command'], $command['args']);
            return;
        }

        // Coba parse sebagai multi-date expenses (ddmmyy format)
        $multiDateExpenses = $this->parser->parseMultiDateExpenses($message);
        if ($multiDateExpenses !== null) {
            $this->handleMultiDateExpenses($sender, $multiDateExpenses);
            return;
        }

        // Coba parse sebagai multiple expense dalam satu baris (makan 50rb + transport 25rb)
        $multipleExpenses = $this->parser->parseMultipleExpenses($message);
        if (!empty($multipleExpenses)) {
            $this->saveExpensesDirectly($sender, $multipleExpenses);
            return;
        }

        // Coba parse sebagai single expense
        $expense = $this->parser->parseExpense($message);

        if ($expense) {
            $this->saveExpensesDirectly($sender, [$expense]);
            return;
        }

        // Tidak dikenali - silent fail, tidak ada respon
    }

    /**
     * Simpan expenses langsung tanpa konfirmasi
     */
    private function saveExpensesDirectly(string $sender, array $expenses, ?string $date = null): void
    {
        // Validasi kategori untuk semua expense
        $validatedExpenses = [];
        $invalidCategories = [];

        foreach ($expenses as $exp) {
            $category = $this->expense->getCategoryByName($exp['category']);
            if (!$category) {
                $invalidCategories[] = $exp['category'];
            } else {
                $validatedExpenses[] = $exp;
            }
        }

        // Jika ada kategori yang tidak valid
        if (!empty($invalidCategories)) {
            $cats = implode(', ', array_unique($invalidCategories));
            $this->fonnte->sendMessage(
                $sender,
                "Kategori tidak ditemukan: $cats\n\nKetik 'kategori' untuk lihat daftar, atau 'tambah kategori <nama>' untuk menambah."
            );
            return;
        }

        // Simpan semua expense
        $savedCount = 0;
        $savedLines = [];
        $totalSaved = 0;

        foreach ($validatedExpenses as $exp) {
            $result = $this->expense->addExpense(
                $exp['category'],
                $exp['amount'],
                $exp['description'] ?? null,
                $date
            );

            if ($result['success']) {
                $savedCount++;
                $totalSaved += $exp['amount'];
                $desc = $exp['description'] ? " ({$exp['description']})" : '';
                $savedLines[] = sprintf(
                    "- %s Rp%s%s",
                    ucfirst($exp['category']),
                    number_format($exp['amount'], 0, ',', '.'),
                    $desc
                );
            }
        }

        if ($savedCount === 0) {
            $this->fonnte->sendMessage($sender, "Gagal menyimpan transaksi.");
            return;
        }

        // Build response message
        $todayTotal = $this->expense->getTodayTotal();

        if ($savedCount === 1) {
            $message = "Tercatat: " . ltrim($savedLines[0], '- ');
        } else {
            $message = "Tercatat {$savedCount} pengeluaran:\n" . implode("\n", $savedLines);
            $message .= sprintf("\nSubtotal: Rp%s", number_format($totalSaved, 0, ',', '.'));
        }

        // Add quick stats
        $label = ($date === null) ? "Total hari ini" : "Total";
        $message .= sprintf("\n\n%s: Rp%s", $label, number_format($todayTotal, 0, ',', '.'));

        $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle multi-date expenses
     */
    private function handleMultiDateExpenses(string $sender, array $expensesByDate): void
    {
        $months = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar',
            '04' => 'Apr', '05' => 'Mei', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Agt', '09' => 'Sep',
            '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        $allSavedLines = [];
        $totalTransactions = 0;

        foreach ($expensesByDate as $date => $expenses) {
            // Validasi kategori untuk expenses di tanggal ini
            $validatedExpenses = [];
            $invalidCategories = [];

            foreach ($expenses as $exp) {
                $category = $this->expense->getCategoryByName($exp['category']);
                if (!$category) {
                    $invalidCategories[] = $exp['category'];
                } else {
                    $validatedExpenses[] = $exp;
                }
            }

            // Skip jika ada kategori invalid di tanggal ini
            if (!empty($invalidCategories)) {
                $cats = implode(', ', array_unique($invalidCategories));
                $allSavedLines[] = "{$date}: Kategori tidak ditemukan: $cats";
                continue;
            }

            // Simpan semua expense untuk tanggal ini
            $dateLines = [];
            foreach ($validatedExpenses as $exp) {
                $result = $this->expense->addExpense(
                    $exp['category'],
                    $exp['amount'],
                    $exp['description'] ?? null,
                    $date
                );

                if ($result['success']) {
                    $totalTransactions++;
                    $desc = $exp['description'] ? " ({$exp['description']})" : '';
                    $dateLines[] = sprintf(
                        "• %s Rp%s%s",
                        ucfirst($exp['category']),
                        number_format($exp['amount'], 0, ',', '.'),
                        $desc
                    );
                }
            }

            if (!empty($dateLines)) {
                // Format date for display (DD MMM YYYY)
                $dateParts = explode('-', $date);
                $day = $dateParts[2];
                $month = $months[$dateParts[1]] ?? $dateParts[1];
                $year = $dateParts[0];
                $displayDate = "{$day} {$month} {$year}";

                $allSavedLines[] = "{$displayDate}:";
                foreach ($dateLines as $line) {
                    $allSavedLines[] = "  {$line}";
                }
            }
        }

        if ($totalTransactions === 0) {
            $this->fonnte->sendMessage($sender, "Gagal menyimpan transaksi.");
            return;
        }

        // Build response message
        $todayTotal = $this->expense->getTodayTotal();

        $message = "Tersimpan {$totalTransactions} transaksi:\n\n";
        $message .= implode("\n", $allSavedLines);
        $message .= sprintf("\n\nTotal: Rp%s", number_format($todayTotal, 0, ',', '.'));

        $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle command
     */
    private function handleCommand(string $sender, string $command, ?string $args): void
    {
        switch ($command) {
            case 'add_category':
                $this->handleAddCategory($sender, $args);
                break;

            case 'delete_last':
                $this->handleDeleteLast($sender);
                break;

            case 'report_weekly':
                $this->handleReport($sender, 'mingguan');
                break;

            case 'report_monthly':
                $this->handleReport($sender, 'bulanan');
                break;

            case 'report_yearly':
                $this->handleReport($sender, 'tahunan');
                break;

            case 'report_custom':
                $this->handleReportCustom($sender, $args);
                break;

            case 'total_today':
                $this->handleTotal($sender, 'hari');
                break;

            case 'total_week':
                $this->handleTotal($sender, 'minggu');
                break;

            case 'total_month':
                $this->handleTotal($sender, 'bulan');
                break;

            case 'total_year':
                $this->handleTotal($sender, 'tahun');
                break;

            case 'total_custom':
                $this->handleTotalCustom($sender, $args);
                break;

            case 'list_categories':
                $this->handleListCategories($sender);
                break;

            case 'help':
                $this->sendHelp($sender);
                break;

            case 'summary_today':
                $this->handleSummary($sender, 'hari');
                break;

            case 'summary_week':
                $this->handleSummary($sender, 'minggu');
                break;

            case 'summary_month':
                $this->handleSummary($sender, 'bulan');
                break;

            case 'summary_year':
                $this->handleSummary($sender, 'tahun');
                break;

            case 'summary_custom':
                $this->handleSummaryCustom($sender, $args);
                break;

            case 'history':
                $this->handleHistory($sender);
                break;

            case 'compare_week':
                $this->handleComparison($sender, 'minggu');
                break;

            case 'compare_month':
                $this->handleComparison($sender, 'bulan');
                break;

            case 'compare_custom':
                $this->handleComparisonCustom($sender, $args);
                break;

            default:
                // Command tidak dikenali - silent fail, tidak ada respon
        }
    }

    /**
     * Handle tambah kategori
     */
    private function handleAddCategory(string $sender, ?string $categoryName): void
    {
        if (empty($categoryName)) {
            $this->fonnte->sendMessage($sender, "Format: tambah kategori <nama>\nContoh: tambah kategori kopi");
            return;
        }

        $result = $this->expense->addCategory($categoryName);
        $this->fonnte->sendMessage($sender, $result['message']);
    }

    /**
     * Handle hapus pengeluaran terakhir
     */
    private function handleDeleteLast(string $sender): void
    {
        $result = $this->expense->deleteLastExpense();
        $this->fonnte->sendMessage($sender, $result['message']);
    }

    /**
     * Handle request report
     */
    private function handleReport(string $sender, string $period): void
    {
        // Kirim pesan loading
        $this->fonnte->sendMessage($sender, "Sedang membuat laporan $period...");

        $result = $this->report->generateReport($period);

        if (!$result['success']) {
            $this->fonnte->sendMessage($sender, $result['message']);
            return;
        }

        // Kirim link download sebagai pesan teks
         $message = "Laporan $period sudah jadi!\n\n";
         $message .= "Download di sini:\n{$result['url']}\n\n";
         $message .= "File: {$result['filename']}\n\n";
         $message .= "⚠️ Link expired dalam 1 jam";

         $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle total pengeluaran
     */
    private function handleTotal(string $sender, string $period): void
    {
        $result = $this->expense->getTotal($period);
        
        // Tambah detail per kategori
        $byCategory = $this->expense->getTotalByCategory($period);
        
        $message = $result['message'];
        
        if (!empty($byCategory)) {
            $message .= "\n\nDetail per kategori:";
            foreach ($byCategory as $item) {
                $message .= sprintf(
                    "\n- %s: Rp%s",
                    ucfirst($item['name']),
                    number_format($item['total'], 0, ',', '.')
                );
            }
        }

        $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle ringkasan (text summary)
     */
    private function handleSummary(string $sender, string $period): void
    {
        $summary = $this->expense->getSummary($period);
        $this->fonnte->sendMessage($sender, $summary);
    }

    /**
     * Handle riwayat transaksi
     */
    private function handleHistory(string $sender): void
    {
        $history = $this->expense->formatHistory(5);
        $this->fonnte->sendMessage($sender, $history);
    }

    /**
     * Handle perbandingan periode
     */
    private function handleComparison(string $sender, string $period): void
    {
        $comparison = $this->expense->getComparison($period);
        $this->fonnte->sendMessage($sender, $comparison);
    }

    // ================================================================
    // CUSTOM PERIOD HANDLERS
    // ================================================================

    /**
     * Error message untuk format custom period yang salah
     */
    private function sendCustomPeriodError(string $sender, string $commandName): void
    {
        $examples = "Format tidak dikenali.\n\nContoh {$commandName}:\n";
        $examples .= "- {$commandName} 2025\n";
        $examples .= "- {$commandName} januari 2025\n";
        $examples .= "- {$commandName} jan 2025\n";
        $examples .= "- {$commandName} 2024-2025\n";
        $examples .= "- {$commandName} jan 2024 hingga jun 2025\n";
        $examples .= "- {$commandName} 01/01/2024 hingga 31/12/2025";

        $this->fonnte->sendMessage($sender, $examples);
    }

    /**
     * Handle report dengan custom period
     */
    private function handleReportCustom(string $sender, ?string $args): void
    {
        $period = $this->parser->parseCustomPeriod($args);

        if (!$period) {
            // Kalau ga ada args, default ke bulanan (bulan ini)
            if (empty($args)) {
                $this->handleReport($sender, 'bulanan');
                return;
            }
            $this->sendCustomPeriodError($sender, 'report');
            return;
        }

        $periodLabel = $this->expense->getCustomPeriodLabel($period);
        $this->fonnte->sendMessage($sender, "Sedang membuat laporan {$periodLabel}...");

        $result = $this->report->generateReportCustom($period);

        if (!$result['success']) {
            $this->fonnte->sendMessage($sender, $result['message']);
            return;
        }

        $message = "Laporan {$periodLabel} sudah jadi!\n\n";
         $message .= "Download di sini:\n{$result['url']}\n\n";
         $message .= "File: {$result['filename']}\n\n";
         $message .= "⚠️ Link expired dalam 1 jam";

         $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle total dengan custom period
     */
    private function handleTotalCustom(string $sender, ?string $args): void
    {
        $period = $this->parser->parseCustomPeriod($args);

        if (!$period) {
            if (empty($args)) {
                $this->handleTotal($sender, 'bulan');
                return;
            }
            $this->sendCustomPeriodError($sender, 'total');
            return;
        }

        $result = $this->expense->getTotalCustom($period);
        $byCategory = $this->expense->getTotalByCategoryCustom($period);

        $message = $result['message'];

        if (!empty($byCategory)) {
            $message .= "\n\nDetail per kategori:";
            foreach ($byCategory as $item) {
                $message .= sprintf(
                    "\n- %s: Rp%s",
                    ucfirst($item['name']),
                    number_format($item['total'], 0, ',', '.')
                );
            }
        }

        $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Handle ringkasan dengan custom period
     */
    private function handleSummaryCustom(string $sender, ?string $args): void
    {
        $period = $this->parser->parseCustomPeriod($args);

        if (!$period) {
            if (empty($args)) {
                $this->handleSummary($sender, 'bulan');
                return;
            }
            $this->sendCustomPeriodError($sender, 'ringkasan');
            return;
        }

        $summary = $this->expense->getSummaryCustom($period);
        $this->fonnte->sendMessage($sender, $summary);
    }

    /**
     * Handle perbandingan dengan custom period
     */
    private function handleComparisonCustom(string $sender, ?string $args): void
    {
        $period = $this->parser->parseCustomPeriod($args);

        if (!$period) {
            if (empty($args)) {
                $this->handleComparison($sender, 'bulan');
                return;
            }
            $this->sendCustomPeriodError($sender, 'perbandingan');
            return;
        }

        // Perbandingan hanya support month dan year
        if (!in_array($period['type'], ['month', 'year'])) {
            $this->fonnte->sendMessage(
                $sender,
                "Perbandingan hanya bisa untuk bulan atau tahun spesifik.\n\nContoh:\n- perbandingan januari 2025\n- perbandingan 2025"
            );
            return;
        }

        $comparison = $this->expense->getComparisonCustom($period);
        $this->fonnte->sendMessage($sender, $comparison);
    }

    /**
     * Handle list kategori
     */
    private function handleListCategories(string $sender): void
    {
        $categories = $this->expense->getAllCategories();

        $message = "Daftar Kategori:\n";
        
        $preset = [];
        $custom = [];
        
        foreach ($categories as $cat) {
            if ($cat['is_custom']) {
                $custom[] = $cat['name'];
            } else {
                $preset[] = $cat['name'];
            }
        }

        $message .= "\nDefault: " . implode(', ', $preset);
        
        if (!empty($custom)) {
            $message .= "\nCustom: " . implode(', ', $custom);
        }

        $message .= "\n\nUntuk menambah kategori:\ntambah kategori <nama>";

        $this->fonnte->sendMessage($sender, $message);
    }

    /**
     * Kirim pesan bantuan
     */
    private function sendHelp(string $sender): void
    {
        $help = "📊 CHATBOT KEUANGAN WHATSAPP

💰 CATAT PENGELUARAN:
<kategori> <nominal> [keterangan]
Contoh:
  makan 50000 warteg
  transport 25rb gojek
  belanja 1.5jt alfamart

Banyak transaksi sekaligus:
  makan 50rb + transport 25rb gojek

(Transaksi langsung tersimpan tanpa konfirmasi)

📅 TANGGAL LAMPAU (format: ddmmyy):
250226
makan 7k nasduk
belanja 100k alfa

260226
makan 5k nasi

📈 LIHAT TOTAL & RINGKASAN:
• total hari ini
• total minggu ini
• total bulan ini
• total tahun ini
• total 2025
• total jan 2025

Ringkasan (dengan persentase per kategori):
• ringkasan hari ini
• ringkasan minggu ini
• ringkasan bulan ini
• ringkasan 2025
• ringkasan jan 2025

📊 PERBANDINGAN (vs periode sebelumnya):
• perbandingan minggu
• perbandingan bulan
• perbandingan jan 2025
• perbandingan 2025

📋 LAPORAN EXCEL (generate & download):
• report mingguan / report bulanan / report tahunan
• report 2025
• report jan 2025
• report 2024-2025
• report jan 2024 hingga jun 2025
• report 01/01/2024 hingga 31/12/2025

⚙️ KATEGORI:
• kategori → lihat daftar kategori
• tambah kategori <nama> → tambah kategori baru

🗑️ LAINNYA:
• hapus terakhir → hapus transaksi terakhir
• riwayat → lihat 5 transaksi terakhir
• bantuan → tampilkan pesan ini

💡 Tips: Ketik command tanpa argumen untuk default (bulan ini)
Contoh: 'report' = laporan bulan ini, 'total' = total bulan ini";

        $this->fonnte->sendMessage($sender, $help);
    }

    /**
     * Kirim pesan tidak dikenali
     */
    private function sendUnknownMessage(string $sender): void
    {
        $message = "Maaf, pesan tidak dikenali.

Contoh catat pengeluaran:
makan 50000 warteg

Ketik 'bantuan' untuk melihat panduan lengkap.";

        $this->fonnte->sendMessage($sender, $message);
    }
}
