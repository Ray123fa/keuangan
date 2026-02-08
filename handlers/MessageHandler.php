<?php
/**
 * MessageHandler - Handle dan route incoming messages dari WhatsApp
 */

require_once __DIR__ . '/../services/FonnteService.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../services/SessionService.php';
require_once __DIR__ . '/../utils/Parser.php';

class MessageHandler
{
    private FonnteService $fonnte;
    private ExpenseService $expense;
    private ReportService $report;
    private SessionService $session;
    private Parser $parser;

    public function __construct()
    {
        $this->fonnte = new FonnteService();
        $this->expense = new ExpenseService();
        $this->report = new ReportService();
        $this->session = new SessionService();
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

        // Cek dulu apakah ada pending expense yang butuh konfirmasi
        if ($this->session->hasPending($sender)) {
            $confirmation = $this->session->parseConfirmation($message);
            
            if ($confirmation !== null) {
                $this->handleConfirmation($sender, $confirmation);
                return;
            }
            // Kalau bukan konfirmasi, clear pending dan lanjut proses normal
            $this->session->clearPending($sender);
        }

        // Coba parse sebagai command dulu
        $command = $this->parser->parseCommand($message);

        if ($command['command']) {
            $this->handleCommand($sender, $command['command'], $command['args']);
            return;
        }

        // Coba parse sebagai multiple expense (makan 50rb + transport 25rb)
        $multipleExpenses = $this->parser->parseMultipleExpenses($message);
        if (!empty($multipleExpenses)) {
            $this->handlePendingExpenses($sender, $multipleExpenses);
            return;
        }

        // Coba parse sebagai single expense
        $expense = $this->parser->parseExpense($message);

        if ($expense) {
            $this->handlePendingExpenses($sender, [$expense]);
            return;
        }

        // Tidak dikenali
        $this->sendUnknownMessage($sender);
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
                $this->sendUnknownMessage($sender);
        }
    }

    /**
     * Handle pending expenses - save ke session untuk konfirmasi
     */
    private function handlePendingExpenses(string $sender, array $expenses): void
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

        // Simpan ke pending dan minta konfirmasi
        $this->session->savePending($sender, $validatedExpenses);
        $confirmMessage = $this->session->formatConfirmationMessage($validatedExpenses);
        $this->fonnte->sendMessage($sender, $confirmMessage);
    }

    /**
     * Handle konfirmasi dari user (y/n/g)
     */
    private function handleConfirmation(string $sender, string $confirmation): void
    {
        $pending = $this->session->getPending($sender);
        
        if (!$pending) {
            $this->fonnte->sendMessage($sender, "Tidak ada transaksi yang menunggu konfirmasi.");
            return;
        }

        // Clear pending setelah diambil
        $this->session->clearPending($sender);

        if ($confirmation === 'no') {
            $this->fonnte->sendMessage($sender, "Dibatalkan.");
            return;
        }

        // Konfirmasi = yes, proses semua expense
        $expenses = $pending['expenses'];
        $savedCount = 0;
        $savedLines = [];
        $totalSaved = 0;

        foreach ($expenses as $exp) {
            $result = $this->expense->addExpense(
                $exp['category'],
                $exp['amount'],
                $exp['description'] ?? null
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
        $message .= sprintf("\n\nTotal hari ini: Rp%s", number_format($todayTotal, 0, ',', '.'));

        $this->fonnte->sendMessage($sender, $message);
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
