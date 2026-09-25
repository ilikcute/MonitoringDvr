# Wireframe Specification (ASCII UI Architecture)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Format** | Text-based High-Fidelity Conceptual Mockup |
| **Versi Dokumen** | 1.2.0 |

---

## 1. Desktop Wireframe: Direktori Toko & Master Aset (`/stores`)

```
+-------------------------------------------------------------------------------------------------------------------------+
| CDAMS  [LAN - Head Office]      [Q Search Toko (Ctrl+K)]             [Theme: ☀️/🌙]   [👤 Syahdat (Teknisi) v]          |
+-------------------------------------------------------------------------------------------------------------------------+
| [DASHBOARD]     | DIREKTORI TOKO (~666 GERAI)                                                                           |
| > TOKO & DVR    |                                                                                                       |
|   CHECKLIST     | Filter: [Search: Kode / Nama...    ] [Region: Semua v] [Status: Active v]                             |
|   PENGGUNA      |                                           [📥 Import Excel]  [📤 Export Excel]  [+ Tambah Toko]       |
|   AUDIT LOG     +-------------------------------------------------------------------------------------------------------+
|                 | KODE | NAMA TOKO         | REGION      | DVR 1 (STATUS & IP)  | DVR 2 (STATUS & IP)  | AKUN | AKSI    |
|                 |------+-------------------+-------------+----------------------+----------------------+------+---------|
|                 | T001 | Grand Central     | Jabodetabek | 10.10.1.200 (Online) | 10.10.1.201 (Online) | 5/5  | [Lihat] |
|                 | T002 | Mall Kelapa Gading| Jabodetabek | 10.10.2.200 (Online) | - (Belum Ada)        | 5/5  | [Lihat] |
|                 | T003 | Pasar Baru        | Jawa Barat  | 10.10.3.200 (Offline)| 10.10.3.201 (Online) | 5/5  | [Lihat] |
|                 | T004 | Ruko Dago         | Jawa Barat  | 10.10.4.200 (Degraded| -                    | 4/5  | [Lihat] |
|                 +-------------------------------------------------------------------------------------------------------+
|                 | Menampilkan 1 - 15 dari 666 toko              [<< Prev] [1] [2] [3] ... [45] [Next >>]                 |
+-------------------------------------------------------------------------------------------------------------------------+
```

---

## 2. Desktop Wireframe: Detail Toko & Kartu DVR (`/stores/:id`)

```
+-------------------------------------------------------------------------------------------------------------------------+
| <- Kembali ke Daftar Toko                                                         [✏️ Edit Toko]  [🗑️ Hapus Toko]       |
+-------------------------------------------------------------------------------------------------------------------------+
| TOKO: T001 - Grand Central                                                                      [Status: ACTIVE]        |
| Wilayah: Jabodetabek   | Subnet: 10.10.1.0/24   | Kontak: Budi Santoso (0812-3456-7890)                                 |
| Alamat: Jl. Merdeka Barat No. 12, Jakarta Pusat                                                                         |
+-------------------------------------------------------------------------------------------------------------------------+
| [ UNIT: DVR 1 - Area Toko & Kasir (Online) ]             [ UNIT: DVR 2 - Area Gudang & Loading (Online) ]               |
+-------------------------------------------------------------------------------------------------------------------------+
| SPESIFIKASI PERANGKAT DVR 1:                                                                                            |
| Merk: Hikvision | Model: DS-7208HQHI-K1 | SN: HIK-2024-998811   [⚡ Quick Ping Test: OK (14ms)]                          |
| IP: 10.10.1.200 | Port: HTTP 80 / RTSP 554 / Media 8000 | Saluran: 8 Channel | Storage: 2.0 TB (Retensi: 30 Hari)        |
| Checklist Terakhir: 25 Sep 2026, 10:30 WIB oleh Syahdat (Teknisi)                  [⚙️ Edit Detail DVR]  [Mulai Checklist]|
+-------------------------------------------------------------------------------------------------------------------------+
| KREDENSIAL 5 AKUN DEPARTEMEN:                                                                                           |
|                                                                                                                         |
| 1. [IC] Inventory Control                                                                                               |
|    Username: ic_t001          Password: [ •••••••••••• ] [👁️ Intip (15s)] [📋 Salin]                                   |
|    Hak Akses: Live View Channel 1-4 & Playback Gudang                                                                   |
|-------------------------------------------------------------------------------------------------------------------------|
| 2. [EDP] IT Support (Administrator)                                                                                     |
|    Username: edp_admin        Password: [ •••••••••••• ] [👁️ Intip (15s)] [📋 Salin]                                   |
|    Hak Akses: Full Administrator (Config IP, Ports, Firmware)                                                           |
|-------------------------------------------------------------------------------------------------------------------------|
| 3. [SPV] Supervisor Area                                                                                                |
|    Username: spv_store        Password: [ •••••••••••• ] [👁️ Intip (15s)] [📋 Salin]                                   |
|    Hak Akses: Live View Only Area Publik & Pintu Masuk                                                                  |
|-------------------------------------------------------------------------------------------------------------------------|
| 4. [DEV] Team Development                                                                                               |
|    Username: dev_growth       Password: [ •••••••••••• ] [👁️ Intip (15s)] [📋 Salin]                                   |
|    Hak Akses: Live View Area Sales & Antrean Kasir                                                                      |
|-------------------------------------------------------------------------------------------------------------------------|
| 5. [AUD] Internal Audit                                                                                                 |
|    Username: aud_fraud        Password: [ •••••••••••• ] [👁️ Intip (15s)] [📋 Salin]                                   |
|    Hak Akses: Playback & Export Rekaman Semua Channel                                                                   |
+-------------------------------------------------------------------------------------------------------------------------+
```

---

## 3. Desktop Wireframe: Rekapitulasi Checklist Lapangan (`/checks`)

```
+-------------------------------------------------------------------------------------------------------------------------+
| REKAPITULASI CHECKLIST LAPANGAN                                                                                         |
| Filter: [Search: Toko / Teknisi...        ] [ ] Hanya Tampilkan Temuan Bermasalah/Abnormal      [Per Page: 15 v]         |
+-------------------------------------------------------------------------------------------------------------------------+
| TANGGAL & WAKTU      | TOKO                 | DVR              | TEKNISI  | PING | JAM NTP     | HDD    | KAMERA | AKSI  |
|----------------------+----------------------+------------------+----------+------+-------------+--------+--------+-------|
| 25 Sep 2026, 10:30 WIB| T001 - Grand Central | DVR 1 (Kasir)    | Syahdat  | [OK] | [⚠️ +240s]  | Normal | 8/8 OK |[Detail]|
| 25 Sep 2026, 09:15 WIB| T001 - Grand Central | DVR 2 (Gudang)   | Syahdat  | [OK] | [OK Synced] | Normal | 8/8 OK |[Detail]|
| 24 Sep 2026, 16:40 WIB| T003 - Pasar Baru    | DVR 1 (Kasir)    | Aris     | [OFF]| [⚠️ +410s]  |[ERR]   | 6/8 OK |[Detail]|
| 24 Sep 2026, 14:10 WIB| T004 - Ruko Dago     | DVR 1 (Kasir)    | Aris     | [OK] | [OK Synced] | Normal | 7/8 OK |[Detail]|
+-------------------------------------------------------------------------------------------------------------------------+
| Menampilkan 1 - 4 dari 120 riwayat checklist                  [<< Prev] [1] [2] [3] ... [8] [Next >>]                   |
+-------------------------------------------------------------------------------------------------------------------------+
```

---

## 4. Desktop Wireframe: Manajemen Pengguna & RBAC (`/users`)

```
+-------------------------------------------------------------------------------------------------------------------------+
| MANAJEMEN PENGGUNA SISTEM & RBAC                                                                    [+ Tambah Pengguna] |
| Filter: [Cari nama / email...             ] [Filter Role: Semua v]                                                      |
+-------------------------------------------------------------------------------------------------------------------------+
| NAMA                 | EMAIL                     | ROLE         | DEPARTEMEN    | STATUS AKTIF | TERDAFTAR   | AKSI     |
|----------------------+---------------------------+--------------+---------------+--------------+-------------+----------|
| Super Admin EDP      | admin@indomaret.test      | [SUPERADMIN] | -             | [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| Syahdat              | teknisi@indomaret.test    | [TECHNICIAN] | -             | [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| Aris                 | ic@indomaret.test         | [TECHNICIAN] | IC (Inventory)| [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| Jurit                | spv@indomaret.test        | [OPERATOR]   | SPV (Area SPV)| [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| ERO                  | dev@indomaret.test        | [OPERATOR]   | DEV (Team Dev)| [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| Cia                  | cia@indomaret.test        | [OPERATOR]   | AUD (Audit)   | [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
| SYI                  | manager@indomaret.test    | [MANAGEMENT] | -             | [🟢 Aktif]   | 24 Sep 2026 | [Edit]   |
+-------------------------------------------------------------------------------------------------------------------------+
```

---

## 5. Modal Wireframe: Edit Detail DVR & Impor Toko

```
+---------------------------------------------------------------+
| MODAL: EDIT DETAIL DVR                                  [ X ] |
+---------------------------------------------------------------+
| Label DVR:      [ DVR 1 - Area Toko & Kasir                 ] |
| Merk:           [ Hikvision                                 ] |
| Model Series:   [ DS-7208HQHI-K1                            ] |
| Serial Number:  [ HIK-2024-998811-REV2                      ] |
| Alamat IP:      [ 10.10.1.200                               ] |
| HTTP Port:      [ 80        ]   RTSP Port:    [ 554         ] |
| Media Port:     [ 8000      ]   Saluran:      [ 8 Channel v ] |
| Kapasitas (TB): [ 2.0       ]   Retensi (Hari): [ 30        ] |
| Status DVR:     [ Online                                  v ] |
+---------------------------------------------------------------+
| [Batal]                                   [💾 Simpan Perubahan]|
+---------------------------------------------------------------+
```

---

## 6. Mobile Wireframe: Tampilan Smartphone Teknisi di Toko (375x812)

```
+-----------------------------------+
| CDAMS Mobile       [WAN: Toko T001]|
+-----------------------------------+
| [ Cari Toko: T001...            Q]|
+-----------------------------------+
| Toko: T001 - Grand Central        |
| Status Toko: ACTIVE               |
+-----------------------------------+
| [ DVR 1 (ON) ]   [ DVR 2 (ON) ]   |
+-----------------------------------+
| SN: HIK-2024-998811               |
| IP: 10.10.1.200 : 80              |
| Retensi: 30 Hari | Storage: 2 TB  |
| Terakhir Dicek: 25 Sep, 10:30 WIB |
+-----------------------------------+
| KREDENSIAL AKUN SAYA (IC):        |
| User: ic_t001                     |
| Pass: [ •••••••• ] [👁️] [📋 Salin] |
+-----------------------------------+
| FORM CHECKLIST CEPAT LAPANGAN:    |
| Ping DVR:         (•) Online ( ) Off
| Sinkron Jam NTP:  ( ) Pas    (•) Beda
| Selisih Jam (dtk):[ 240         ] |
| Status HDD:       [ Normal      v]|
| Retensi Rekaman:  [ 30 Hari     ] |
| Fisik Kamera:     Normal: [ 8 ]   |
|                   Rusak:  [ 0 ]   |
| Catatan:                          |
| [ Jam terlambat 4 menit, sinkron ]|
+-----------------------------------+
| [ >>> SUBMIT HASIL CHECKLIST <<< ]|
+-----------------------------------+
| [🏠 Dashboard] [📋 Cek] [⚙️ Akun] |
+-----------------------------------+
```