<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function exportCsv(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(array $headers, iterable $rows, string $filename, string $sheetTitle = 'Report'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
        ];
        $lastCol = count($headers);
        $sheet->getStyle([1, 1, $lastCol, 1])->applyFromArray($headerStyle);

        // Write data
        $rowNum = 2;
        foreach ($rows as $row) {
            $col = 1;
            foreach ($row as $value) {
                $sheet->setCellValue([$col, $rowNum], $value);
                $col++;
            }
            $rowNum++;
        }

        // Auto-size columns
        for ($i = 1; $i <= $lastCol; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportMultiSheet(array $sheets, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        foreach ($sheets as $index => $sheetData) {
            if ($index > 0) {
                $spreadsheet->createSheet();
            }
            $sheet = $spreadsheet->setActiveSheetIndex($index);
            $sheet->setTitle($sheetData['title'] ?? "Sheet " . ($index + 1));

            // Write headers
            foreach ($sheetData['headers'] as $col => $header) {
                $sheet->setCellValue([$col + 1, 1], $header);
            }

            // Style headers
            $lastCol = count($sheetData['headers']);
            $sheet->getStyle([1, 1, $lastCol, 1])->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ]);

            // Write data
            $rowNum = 2;
            foreach ($sheetData['rows'] as $row) {
                $col = 1;
                foreach ($row as $value) {
                    $sheet->setCellValue([$col, $rowNum], $value);
                    $col++;
                }
                $rowNum++;
            }

            for ($i = 1; $i <= $lastCol; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
