<?php
declare(strict_types=1);

namespace App\Application\Services;
/**
 * ReportService - Generate laporan dan upload ke File.io
 */

use App\Infrastructure\Reporting\ExcelGenerator;
use CURLFile;

class ReportService
{
    private ExpenseService $expenseService;

    public function __construct()
    {
        $this->expenseService = new ExpenseService();
    }

    /**
     * Generate report Excel dan upload ke File.io
     */
    public function generateReport(string $period): array
    {
        // Ambil data pengeluaran
        $expenses = $this->expenseService->getExpenses($period);
        $totalByCategory = $this->expenseService->getTotalByCategory($period);
        $totalResult = $this->expenseService->getTotal($period);

        if (empty($expenses)) {
            return [
                'success' => false,
                'message' => "Tidak ada pengeluaran untuk periode " . $this->getPeriodLabel($period) . "."
            ];
        }

        // Generate Excel
        $generator = new ExcelGenerator();
        $filePath = $generator->generate($expenses, $totalByCategory, $totalResult['total'], $period);

        if (!$filePath) {
            return [
                'success' => false,
                'message' => 'Gagal membuat file Excel.'
            ];
        }

        // Upload ke File.io
        $uploadResult = $this->uploadToFileIo($filePath);

        // Hapus file lokal
        @unlink($filePath);

        if (!$uploadResult['success']) {
            return [
                'success' => false,
                'message' => 'Gagal upload file: ' . $uploadResult['message']
            ];
        }

        return [
            'success' => true,
            'url' => $uploadResult['url'],
            'message' => sprintf(
                "Laporan %s\nTotal: Rp%s\nJumlah transaksi: %d",
                $this->getPeriodLabel($period),
                number_format($totalResult['total'], 0, ',', '.'),
                count($expenses)
            ),
            'filename' => $this->getFilename($period)
        ];
    }

    /**
     * Upload file ke file hosting service
     */
    private function uploadToFileIo(string $filePath): array
    {
        // Primary: tmpfiles.org
        $result = $this->tryUploadTmpFiles($filePath);
        if ($result['success']) {
            return $result;
        }

        // Fallback: 0x0.st
        $result = $this->tryUpload0x0($filePath);
        return $result;
    }

    /**
     * Try upload to tmpfiles.org
     */
    private function tryUploadTmpFiles(string $filePath): array
    {
        $curl = curl_init();

        $postFields = [
            'file' => new CURLFile($filePath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', basename($filePath))
        ];

        $curlOptions = [
            CURLOPT_URL => 'https://tmpfiles.org/api/v1/upload',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        $curlOptions = array_merge($curlOptions, $this->getSslCurlOptions());
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            error_log('tmpfiles.org curl error: ' . $error);
            return ['success' => false, 'message' => $error];
        }

        error_log('tmpfiles.org response: ' . $response);

        if (empty($response)) {
            return ['success' => false, 'message' => 'Empty response from tmpfiles.org'];
        }

        $result = json_decode($response, true);

        if ($result && isset($result['status']) && $result['status'] === 'success' && isset($result['data']['url'])) {
            // Convert view URL to direct download URL
            $url = str_replace('tmpfiles.org/', 'tmpfiles.org/dl/', $result['data']['url']);
            // Ensure HTTPS
            $url = str_replace('http://', 'https://', $url);
            return [
                'success' => true,
                'url' => $url
            ];
        }

        return ['success' => false, 'message' => 'tmpfiles.org upload failed'];
    }

    /**
     * Try upload to 0x0.st
     */
    private function tryUpload0x0(string $filePath): array
    {
        $curl = curl_init();

        $postFields = [
            'file' => new CURLFile($filePath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', basename($filePath))
        ];

        $curlOptions = [
            CURLOPT_URL => 'https://0x0.st',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        $curlOptions = array_merge($curlOptions, $this->getSslCurlOptions());
        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            error_log('0x0.st curl error: ' . $error);
            return ['success' => false, 'message' => $error];
        }

        error_log('0x0.st response: ' . $response);

        if (empty($response)) {
            return ['success' => false, 'message' => 'Empty response from 0x0.st'];
        }

        // 0x0.st returns plain URL
        $url = trim($response);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => true,
                'url' => $url
            ];
        }

        return ['success' => false, 'message' => '0x0.st upload failed'];
    }

    /**
     * Generate filename berdasarkan periode
     */
    private function getFilename(string $period): string
    {
        $date = date('Y-m-d');
        return "laporan_keuangan_{$period}_{$date}.xlsx";
    }

    /**
     * Generate filename untuk custom period
     * @param array $period
     * @return string
     */
    private function getFilenameCustom(array $period): string
    {
        $months = [
            1 => 'januari', 2 => 'februari', 3 => 'maret',
            4 => 'april', 5 => 'mei', 6 => 'juni',
            7 => 'juli', 8 => 'agustus', 9 => 'september',
            10 => 'oktober', 11 => 'november', 12 => 'desember',
        ];

        switch ($period['type']) {
            case 'year':
                return "laporan_keuangan_{$period['year']}.xlsx";
            case 'month':
                return "laporan_keuangan_{$months[$period['month']]}_{$period['year']}.xlsx";
            case 'year_range':
                return "laporan_keuangan_{$period['start_year']}-{$period['end_year']}.xlsx";
            case 'month_range':
            case 'date_range':
                $start = str_replace(['/', ' '], '-', $period['start_label']);
                $end = str_replace(['/', ' '], '-', $period['end_label']);
                return "laporan_keuangan_{$start}_sd_{$end}.xlsx";
            default:
                return "laporan_keuangan_custom_" . date('Y-m-d') . ".xlsx";
        }
    }

    /**
     * Generate report Excel untuk custom period dan upload
     * @param array $period Parsed period dari Parser::parseCustomPeriod()
     * @return array
     */
    public function generateReportCustom(array $period): array
    {
        $expenses = $this->expenseService->getExpensesCustom($period);
        $totalByCategory = $this->expenseService->getTotalByCategoryCustom($period);
        $totalResult = $this->expenseService->getTotalCustom($period);
        $periodLabel = $this->expenseService->getCustomPeriodLabel($period);

        if (empty($expenses)) {
            return [
                'success' => false,
                'message' => "Tidak ada pengeluaran untuk periode {$periodLabel}."
            ];
        }

        // Generate Excel - pass the period array
        $generator = new ExcelGenerator();
        $filePath = $generator->generate($expenses, $totalByCategory, $totalResult['total'], $period);

        if (!$filePath) {
            return [
                'success' => false,
                'message' => 'Gagal membuat file Excel.'
            ];
        }

        // Upload
        $uploadResult = $this->uploadToFileIo($filePath);

        // Hapus file lokal
        @unlink($filePath);

        if (!$uploadResult['success']) {
            return [
                'success' => false,
                'message' => 'Gagal upload file: ' . $uploadResult['message']
            ];
        }

        return [
            'success' => true,
            'url' => $uploadResult['url'],
            'message' => sprintf(
                "Laporan %s\nTotal: Rp%s\nJumlah transaksi: %d",
                $periodLabel,
                number_format($totalResult['total'], 0, ',', '.'),
                count($expenses)
            ),
            'filename' => $this->getFilenameCustom($period)
        ];
    }

    /**
     * Get label periode
     */
    private function getPeriodLabel(string $period): string
    {
        switch ($period) {
            case 'mingguan':
            case 'minggu':
                return 'Mingguan';
            case 'bulanan':
            case 'bulan':
                return 'Bulanan';
            case 'tahunan':
            case 'tahun':
                return 'Tahunan';
            default:
                return ucfirst($period);
        }
    }

    private function getSslCurlOptions(): array
    {
        if (APP_ENV === 'production') {
            return [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ];
        }

        return [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
    }
}
