<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Reporting\ExcelGenerator;
use App\Repositories\ExpenseRepository;

final class ReportController
{
    private ExpenseRepository $expenses;

    public function __construct()
    {
        $this->expenses = new ExpenseRepository(Connection::get());
    }

    public function export(): void
    {
        $errorMessage = null;
        $period = $this->resolvePeriod($_GET, $errorMessage);

        if ($period === null) {
            Session::flash('error', $errorMessage ?? 'Parameter export laporan tidak valid.');
            $this->redirect('/admin/expenses');
        }

        $startDate = (string) $period['start_date'];
        $endDate = (string) $period['end_date'];

        $rows = $this->expenses->findByDateRange($startDate, $endDate);

        if ($rows === []) {
            Session::flash('error', 'Tidak ada data pengeluaran untuk periode export yang dipilih.');
            $this->redirect('/admin/expenses');
        }

        $totalByCategory = $this->expenses->sumByCategoryDateRange($startDate, $endDate);
        $grandTotal = $this->expenses->totalByDateRange($startDate, $endDate);

        $generator = new ExcelGenerator();
        $filePath = $generator->generate($rows, $totalByCategory, (float) $grandTotal, $period['excel_period']);

        if ($filePath === null || !is_file($filePath)) {
            Session::flash('error', 'Gagal membuat file export laporan. Silakan coba lagi.');
            $this->redirect('/admin/expenses');
        }

        $this->streamAndDelete($filePath, $this->buildFilename($period['slug']));
    }

    private function resolvePeriod(array $input, ?string &$errorMessage): ?array
    {
        $mode = isset($input['mode']) ? trim((string) $input['mode']) : 'date_range';

        return match ($mode) {
            'date_range' => $this->resolveDateRangePeriod($input, $errorMessage),
            'monthly' => $this->resolveMonthlyPeriod($input, $errorMessage),
            'yearly' => $this->resolveYearlyPeriod($input, $errorMessage),
            default => null,
        };
    }

    private function resolveDateRangePeriod(array $input, ?string &$errorMessage): ?array
    {
        $startDate = $this->parseDate($input['start_date'] ?? null);
        $endDate = $this->parseDate($input['end_date'] ?? null);

        if ($startDate === null || $endDate === null) {
            $errorMessage = 'Rentang tanggal export tidak valid. Gunakan format tanggal yang benar.';
            return null;
        }

        if ($startDate > $endDate) {
            $errorMessage = 'Rentang tanggal export tidak valid. Tanggal mulai harus sebelum atau sama dengan tanggal akhir.';
            return null;
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'slug' => $startDate . '_sd_' . $endDate,
            'excel_period' => [
                'type' => 'date_range',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_label' => date('d/m/Y', strtotime($startDate)),
                'end_label' => date('d/m/Y', strtotime($endDate)),
            ],
        ];
    }

    private function resolveMonthlyPeriod(array $input, ?string &$errorMessage): ?array
    {
        $year = $this->parseYear($input['month_year'] ?? ($input['year'] ?? null));
        $month = $this->parseMonth($input['month'] ?? null);

        if ($year === null || $month === null) {
            $errorMessage = 'Periode bulanan tidak valid. Pilih bulan dan tahun yang benar.';
            return null;
        }

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime($startDate)));

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'slug' => sprintf('%04d-%02d', $year, $month),
            'excel_period' => [
                'type' => 'month',
                'year' => $year,
                'month' => $month,
            ],
        ];
    }

    private function resolveYearlyPeriod(array $input, ?string &$errorMessage): ?array
    {
        $year = $this->parseYear($input['yearly_year'] ?? ($input['year'] ?? null));
        if ($year === null) {
            $errorMessage = 'Periode tahunan tidak valid. Pilih tahun yang benar.';
            return null;
        }

        return [
            'start_date' => sprintf('%04d-01-01', $year),
            'end_date' => sprintf('%04d-12-31', $year),
            'slug' => (string) $year,
            'excel_period' => [
                'type' => 'year',
                'year' => $year,
            ],
        ];
    }

    private function parseDate(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $value = trim($raw);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $value;
    }

    private function parseYear(mixed $raw): ?int
    {
        if (is_int($raw)) {
            $value = $raw;
        } elseif (is_string($raw) && preg_match('/^\d{4}$/', trim($raw))) {
            $value = (int) trim($raw);
        } else {
            return null;
        }

        return ($value >= 2000 && $value <= 2100) ? $value : null;
    }

    private function parseMonth(mixed $raw): ?int
    {
        if (is_int($raw)) {
            $value = $raw;
        } elseif (is_string($raw) && preg_match('/^\d{1,2}$/', trim($raw))) {
            $value = (int) trim($raw);
        } else {
            return null;
        }

        return ($value >= 1 && $value <= 12) ? $value : null;
    }

    private function buildFilename(string $slug): string
    {
        $dateStamp = date('Y-m-d');
        return 'laporan_keuangan_' . $slug . '_' . $dateStamp . '.xlsx';
    }

    private function streamAndDelete(string $filePath, string $filename): never
    {
        $fileSize = filesize($filePath);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
        if (is_int($fileSize)) {
            header('Content-Length: ' . (string) $fileSize);
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        readfile($filePath);
        if (is_file($filePath)) {
            unlink($filePath);
        }
        exit;
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
