<?php

namespace App\Services;

use App\Models\Dvr;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreSpreadsheetService
{
    /**
     * Ekspor daftar toko dan DVR ke format XLSX atau CSV.
     */
    public function export(Collection|array $stores, string $format = 'xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CDAMS Stores');

        // Header
        $headers = [
            'A1' => 'Kode Toko',
            'B1' => 'Nama Toko',
            'C1' => 'Region / Wilayah',
            'D1' => 'Status Toko',
            'E1' => 'IP Subnet Toko',
            'F1' => 'Kontak Person',
            'G1' => 'Telepon',
            'H1' => 'DVR 1 Label',
            'I1' => 'DVR 1 IP',
            'J1' => 'DVR 1 Port (HTTP/RTSP)',
            'K1' => 'DVR 1 Status',
            'L1' => 'DVR 2 Label',
            'M1' => 'DVR 2 IP',
            'N1' => 'DVR 2 Port (HTTP/RTSP)',
            'O1' => 'DVR 2 Status',
            'P1' => 'Akun Ready',
        ];

        foreach ($headers as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }

        // Styling header
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);

        $row = 2;
        foreach ($stores as $store) {
            $dvr1 = $store->dvrs->firstWhere('dvr_index', 1);
            $dvr2 = $store->dvrs->firstWhere('dvr_index', 2);

            $readyAccounts = 0;
            $totalAccounts = 0;
            foreach ($store->dvrs as $dvr) {
                $totalAccounts += 5;
                $readyAccounts += $dvr->accounts->where('is_active', true)->count();
            }

            $sheet->setCellValue("A{$row}", $store->store_code);
            $sheet->setCellValue("B{$row}", $store->store_name);
            $sheet->setCellValue("C{$row}", $store->region);
            $sheet->setCellValue("D{$row}", $store->status);
            $sheet->setCellValue("E{$row}", $store->ip_subnet ?? '-');
            $sheet->setCellValue("F{$row}", $store->contact_person ?? '-');
            $sheet->setCellValue("G{$row}", $store->phone ?? '-');

            $sheet->setCellValue("H{$row}", $dvr1 ? $dvr1->label : '-');
            $sheet->setCellValue("I{$row}", $dvr1 ? $dvr1->ip_address : '-');
            $sheet->setCellValue("J{$row}", $dvr1 ? "{$dvr1->http_port}/{$dvr1->rtsp_port}" : '-');
            $sheet->setCellValue("K{$row}", $dvr1 ? $dvr1->status : '-');

            $sheet->setCellValue("L{$row}", $dvr2 ? $dvr2->label : '-');
            $sheet->setCellValue("M{$row}", $dvr2 ? $dvr2->ip_address : '-');
            $sheet->setCellValue("N{$row}", $dvr2 ? "{$dvr2->http_port}/{$dvr2->rtsp_port}" : '-');
            $sheet->setCellValue("O{$row}", $dvr2 ? $dvr2->status : '-');

            $sheet->setCellValue("P{$row}", "{$readyAccounts}/" . max(5, $totalAccounts));

            $row++;
        }

        // Auto size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'cdams_stores_export_' . date('Ymd_His') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');

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

    /**
     * Download format template impor untuk toko dan DVR.
     */
    public function downloadTemplate(string $format = 'xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Toko');

        $headers = [
            'A1' => 'store_code',
            'B1' => 'store_name',
            'C1' => 'region',
            'D1' => 'address',
            'E1' => 'ip_subnet',
            'F1' => 'contact_person',
            'G1' => 'phone',
            'H1' => 'status',
            'I1' => 'dvr1_ip',
            'J1' => 'dvr1_label',
            'K1' => 'dvr1_brand',
            'L1' => 'dvr2_ip',
            'M1' => 'dvr2_label',
            'N1' => 'dvr2_brand',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        // Contoh data baris 1
        $sheet->setCellValue('A2', 'T001');
        $sheet->setCellValue('B2', 'Toko Grand Central');
        $sheet->setCellValue('C2', 'Jabodetabek');
        $sheet->setCellValue('D2', 'Jl. Merdeka Barat No. 12');
        $sheet->setCellValue('E2', '10.10.1.0/24');
        $sheet->setCellValue('F2', 'Bambang');
        $sheet->setCellValue('G2', '081234567890');
        $sheet->setCellValue('H2', 'Active');
        $sheet->setCellValue('I2', '192.168.25.200');
        $sheet->setCellValue('J2', 'DVR 1 - Area Kasir & Toko');
        $sheet->setCellValue('K2', 'Hikvision');
        $sheet->setCellValue('L2', '192.168.25.201');
        $sheet->setCellValue('M2', 'DVR 2 - Area Gudang');
        $sheet->setCellValue('N2', 'Hikvision');

        // Auto size
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'cdams_template_impor_toko.' . ($format === 'csv' ? 'csv' : 'xlsx');

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

    /**
     * Impor data toko dan DVR dari file yang diunggah.
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return ['imported' => 0, 'updated' => 0, 'errors' => ['File spreadsheet kosong atau hanya berisi header.']];
        }

        $headerRow = array_shift($rows);
        $importedCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $storeCode = trim((string) ($row['A'] ?? ''));
            $storeName = trim((string) ($row['B'] ?? ''));
            $region = trim((string) ($row['C'] ?? ''));

            if (empty($storeCode) || empty($storeName)) {
                continue;
            }

            try {
                $store = Store::firstOrNew(['store_code' => $storeCode]);
                $isNew = !$store->exists;

                $store->store_name = $storeName;
                $store->region = !empty($region) ? $region : 'General';
                $store->address = trim((string) ($row['D'] ?? '')) ?: null;
                $store->ip_subnet = trim((string) ($row['E'] ?? '')) ?: null;
                $store->contact_person = trim((string) ($row['F'] ?? '')) ?: null;
                $store->phone = trim((string) ($row['G'] ?? '')) ?: null;
                $statusVal = ucfirst(strtolower(trim((string) ($row['H'] ?? 'Active'))));
                $store->status = in_array($statusVal, ['Active', 'Renovation', 'Closed']) ? $statusVal : 'Active';
                $store->save();

                if ($isNew) {
                    $importedCount++;
                } else {
                    $updatedCount++;
                }

                // DVR 1
                $dvr1Ip = trim((string) ($row['I'] ?? '')) ?: '192.168.25.200';
                $dvr1Label = trim((string) ($row['J'] ?? '')) ?: 'DVR 1 - Area Kasir & Toko';
                $dvr1Brand = trim((string) ($row['K'] ?? '')) ?: 'Hikvision';

                Dvr::firstOrCreate(
                    ['store_id' => $store->id, 'dvr_index' => 1],
                    [
                        'label' => $dvr1Label,
                        'ip_address' => $dvr1Ip,
                        'brand' => $dvr1Brand,
                        'status' => 'Offline',
                    ]
                );

                // DVR 2 (opsional jika dvr2_ip terisi)
                $dvr2Ip = trim((string) ($row['L'] ?? ''));
                if (!empty($dvr2Ip)) {
                    $dvr2Label = trim((string) ($row['M'] ?? '')) ?: 'DVR 2 - Area Gudang';
                    $dvr2Brand = trim((string) ($row['N'] ?? '')) ?: 'Hikvision';

                    Dvr::firstOrCreate(
                        ['store_id' => $store->id, 'dvr_index' => 2],
                        [
                            'label' => $dvr2Label,
                            'ip_address' => $dvr2Ip,
                            'brand' => $dvr2Brand,
                            'status' => 'Offline',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                $errors[] = "Baris {$rowNum} ({$storeCode}): " . $e->getMessage();
            }
        }

        return [
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'errors' => $errors,
        ];
    }
}
