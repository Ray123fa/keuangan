<?php
declare(strict_types=1);

namespace App\Infrastructure\Reporting;
/**
 * ExcelGenerator - Generate file Excel untuk laporan
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Exception;

class ExcelGenerator
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir();
    }

    /**
     * Generate file Excel laporan
     * @param array $expenses
     * @param array $totalByCategory
     * @param float $grandTotal
     * @param string|array $period String untuk legacy period, array untuk custom period
     */
    public function generate(array $expenses, array $totalByCategory, float $grandTotal, $period): ?string
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // Sheet 1: Detail Pengeluaran
            $this->createDetailSheet($spreadsheet, $expenses, $grandTotal, $period);
            
            // Sheet 2: Ringkasan per Kategori
            $this->createSummarySheet($spreadsheet, $totalByCategory, $grandTotal);

            // Save file
            $periodSlug = is_array($period) ? $this->getPeriodSlug($period) : $period;
            $filename = 'laporan_' . $periodSlug . '_' . date('Y-m-d_His') . '.xlsx';
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . $filename;

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return $filePath;
        } catch (Exception $e) {
            error_log('Excel generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buat sheet detail pengeluaran
     * @param Spreadsheet $spreadsheet
     * @param array $expenses
     * @param float $grandTotal
     * @param string|array $period
     */
    private function createDetailSheet(Spreadsheet $spreadsheet, array $expenses, float $grandTotal, $period): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        // Header title
        $periodLabel = $this->getPeriodLabel($period);
        $sheet->setCellValue('A1', "LAPORAN PENGELUARAN $periodLabel");
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Tanggal: ' . date('d/m/Y'));
        $sheet->mergeCells('A2:E2');

        // Table header
        $headers = ['No', 'Tanggal', 'Kategori', 'Deskripsi', 'Jumlah'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A4:E4')->applyFromArray($headerStyle);

        // Data
        $row = 5;
        $no = 1;
        foreach ($expenses as $expense) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($expense['created_at'])));
            $sheet->setCellValue('C' . $row, ucfirst($expense['category_name']));
            $sheet->setCellValue('D' . $row, $expense['description'] ?? '-');
            $sheet->setCellValue('E' . $row, $expense['amount']);
            
            $sheet->getStyle('E' . $row)->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            
            $row++;
            $no++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('D' . $row, 'TOTAL');
        $sheet->setCellValue('E' . $row, $grandTotal);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Borders for data
        $lastRow = $row;
        $sheet->getStyle("A4:E{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(18);
    }

    /**
     * Buat sheet ringkasan per kategori
     */
    private function createSummarySheet(Spreadsheet $spreadsheet, array $totalByCategory, float $grandTotal): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan');

        // Header
        $sheet->setCellValue('A1', 'RINGKASAN PER KATEGORI');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Kategori');
        $sheet->setCellValue('C3', 'Total');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A3:C3')->applyFromArray($headerStyle);

        // Data
        $row = 4;
        $no = 1;
        foreach ($totalByCategory as $item) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, ucfirst($item['name']));
            $sheet->setCellValue('C' . $row, $item['total']);
            
            $sheet->getStyle('C' . $row)->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            
            $row++;
            $no++;
        }

        // Total
        $sheet->setCellValue('B' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, $grandTotal);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Borders
        $lastRow = $row;
        $sheet->getStyle("A3:C{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
    }

    /**
     * Get period label
     * @param string|array $period
     * @return string
     */
    private function getPeriodLabel($period): string
    {
        // Custom period (array from Parser::parseCustomPeriod)
        if (is_array($period)) {
            $months = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            switch ($period['type']) {
                case 'year':
                    return "TAHUN {$period['year']}";
                case 'month':
                    return strtoupper($months[$period['month']]) . " {$period['year']}";
                case 'year_range':
                    return "{$period['start_year']} - {$period['end_year']}";
                case 'month_range':
                    return strtoupper("{$period['start_label']} - {$period['end_label']}");
                case 'date_range':
                    return "{$period['start_label']} - {$period['end_label']}";
                default:
                    return 'CUSTOM';
            }
        }

        // Legacy string period
        switch ($period) {
            case 'mingguan':
            case 'minggu':
                return 'MINGGUAN';
            case 'bulanan':
            case 'bulan':
                return 'BULANAN';
            case 'tahunan':
            case 'tahun':
                return 'TAHUNAN';
            default:
                return strtoupper($period);
        }
    }

    /**
     * Get slug untuk filename dari custom period
     * @param array $period
     * @return string
     */
    private function getPeriodSlug(array $period): string
    {
        $months = [
            1 => 'januari', 2 => 'februari', 3 => 'maret',
            4 => 'april', 5 => 'mei', 6 => 'juni',
            7 => 'juli', 8 => 'agustus', 9 => 'september',
            10 => 'oktober', 11 => 'november', 12 => 'desember',
        ];

        switch ($period['type']) {
            case 'year':
                return "tahun_{$period['year']}";
            case 'month':
                return $months[$period['month']] . "_{$period['year']}";
            case 'year_range':
                return "{$period['start_year']}-{$period['end_year']}";
            case 'month_range':
            case 'date_range':
                $start = str_replace(['/', ' '], '-', $period['start_label']);
                $end = str_replace(['/', ' '], '-', $period['end_label']);
                return strtolower("{$start}_sd_{$end}");
            default:
                return 'custom';
        }
    }
}
