<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChecklistSpreadsheetService
{
    /**
     * Ekspor daftar hasil checklist lapangan ke format XLSX atau CSV.
     */
    public function export(Collection|array $checks, string $format = 'xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Checklist Lapangan');

        // Header Kolom
        $headers = [
            'A1' => 'No',
            'B1' => 'Tanggal & Jam Cek',
            'C1' => 'Kode Toko',
            'D1' => 'Nama Toko',
            'E1' => 'Region / Wilayah',
            'F1' => 'Unit DVR',
            'G1' => 'Merk & Seri DVR',
            'H1' => 'Serial Number (SN)',
            'I1' => 'IP Address DVR',
            'J1' => 'Teknisi Pemeriksa',
            'K1' => 'Status Ping',
            'L1' => 'Sinkron Jam NTP',
            'M1' => 'Selisih Jam (Detik)',
            'N1' => 'Kondisi Harddisk',
            'O1' => 'Retensi Rekaman (Hari)',
            'P1' => 'Kamera Normal',
            'Q1' => 'Kamera Rusak',
            'R1' => 'Mode Jaringan',
            'S1' => 'Status Temuan Kendala',
            'T1' => 'Catatan Lapangan',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        // Header Styling
        $sheet->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:T1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        $sheet->getStyle('A1:T1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $row = 2;
        $no = 1;

        foreach ($checks as $check) {
            $dvr = $check->dvr;
            $store = $dvr?->store;
            $checker = $check->checker;

            $issues = [];
            if (!$check->is_ping_online) {
                $issues[] = 'Ping Offline';
            }
            if ($check->camera_broken_count > 0) {
                $issues[] = "{$check->camera_broken_count} Kamera Rusak";
            }
            if ($check->hdd_status !== 'Normal') {
                $issues[] = "HDD {$check->hdd_status}";
            }
            if (!$check->is_time_synced) {
                $issues[] = "Jam Out of Sync ({$check->time_difference_seconds}s)";
            }

            $temuanText = count($issues) > 0 ? implode('; ', $issues) : 'Normal (Optimal)';
            $formattedDate = $check->check_timestamp ? $check->check_timestamp->format('d/m/Y H:i') . ' WIB' : '-';

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $formattedDate);
            $sheet->setCellValue("C{$row}", $store ? $store->store_code : '-');
            $sheet->setCellValue("D{$row}", $store ? $store->store_name : '-');
            $sheet->setCellValue("E{$row}", $store ? $store->region : '-');
            $sheet->setCellValue("F{$row}", $dvr ? $dvr->label : '-');
            $sheet->setCellValue("G{$row}", $dvr ? "{$dvr->brand} {$dvr->model_series}" : '-');
            $sheet->setCellValue("H{$row}", $dvr ? ($dvr->serial_number ?: '-') : '-');
            $sheet->setCellValue("I{$row}", $dvr ? $dvr->ip_address : '-');
            $sheet->setCellValue("J{$row}", $checker ? $checker->name : 'Teknisi');
            $sheet->setCellValue("K{$row}", $check->is_ping_online ? 'Online' : 'Offline');
            $sheet->setCellValue("L{$row}", $check->is_time_synced ? 'Sinkron' : 'Out of Sync');
            $sheet->setCellValue("M{$row}", $check->time_difference_seconds);
            $sheet->setCellValue("N{$row}", $check->hdd_status);
            $sheet->setCellValue("O{$row}", $check->record_retention_days ?? '-');
            $sheet->setCellValue("P{$row}", $check->camera_working_count);
            $sheet->setCellValue("Q{$row}", $check->camera_broken_count);
            $sheet->setCellValue("R{$row}", $check->network_type);
            $sheet->setCellValue("S{$row}", $temuanText);
            $sheet->setCellValue("T{$row}", $check->notes ?? '-');

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("P{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("R{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight baris temuan bermasalah jika ada isu
            if (count($issues) > 0) {
                $sheet->getStyle("S{$row}")->getFont()->getColor()->setRGB('DC2626');
                $sheet->getStyle("S{$row}")->getFont()->setBold(true);
            } else {
                $sheet->getStyle("S{$row}")->getFont()->getColor()->setRGB('059669');
            }

            $no++;
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'CDAMS_Checklist_Report_' . date('Ymd_His') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');

        return new StreamedResponse(function () use ($spreadsheet, $format) {
            if ($format === 'csv') {
                $writer = new Csv($spreadsheet);
            } else {
                $writer = new Xlsx($spreadsheet);
            }
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
