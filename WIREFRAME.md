# Wireframe Specification (ASCII UI Architecture)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Format** | Text-based High-Fidelity Conceptual Mockup |

---

## 1. Desktop Wireframe: Dashboard & Direktori Toko (1920x1080)

```
+-------------------------------------------------------------------------------------------------------------+
| CDAMS - CCTV Asset & Access Management            [Network: LAN - Head Office (10.0.8.2)]   [User: Admin v] |
+-------------------------------------------------------------------------------------------------------------+
| [DASHBOARD]     | Quick Filters:                                                                            |
| > STORES (666)  | [Search: Code / Store Name... (Ctrl+K)] [Region: All v] [Status: All v]  [+ Export Excel] |
|   DVR Assets    +-------------------------------------------------------------------------------------------+
|   Checklists    | KODE | NAMA TOKO        | REGION      | DVR 1 (STATUS)   | DVR 2 (STATUS)   | AKUN  | AKSI   |
|   Audit Trail   |------+------------------+-------------+------------------+------------------+-------+--------|
|   Settings      | T001 | Grand Central    | Jabodetabek | 10.10.1.200 (ON) | 10.10.1.201 (ON) | 5/5   | [View] |
|                 | T002 | Mall Kelapa      | Jabodetabek | 10.10.2.200 (ON) | -                | 5/5   | [View] |
|                 | T003 | Pasar Baru       | Jawa Barat  | 10.10.3.200 (ERR)| 10.10.3.201 (ON) | 5/5   | [View] |
|                 | T004 | Ruko Dago        | Jawa Barat  | 10.10.4.200 (OFF)| -                | 4/5   | [View] |
|                 +-------------------------------------------------------------------------------------------+
|                 | Showing 1 - 4 of 666 stores               [<< Prev] [1] [2] [3] ... [167] [Next >>]       |
+-------------------------------------------------------------------------------------------------------------+
```

---

## 2. Desktop Wireframe: Detail Toko & Modal 5 Akun Departemen

```
+-----------------------------------------------------------------------------------------------+
| DETAIL TOKO: T001 - Grand Central (Region: Jabodetabek)                     [Close [X]]       |
+-----------------------------------------------------------------------------------------------+
| Alamat: Jl. Merdeka Barat No. 12, Jakarta Pusat       Subnet Jaringan: 10.10.1.0/24           |
+-----------------------------------------------------------------------------------------------+
| [ Tab: DVR 1 - Area Toko & Kasir (Online) ]       [ Tab: DVR 2 - Area Gudang (Online) ]       |
+-----------------------------------------------------------------------------------------------+
| Info Perangkat:                                                                               |
| Merk: Hikvision DS-7208HQHI-K1 | IP: 10.10.1.200 | Port: 80 / 554 / 8000 | Total: 8 Channel   |
+-----------------------------------------------------------------------------------------------+
| MAPPING 5 AKUN DEPARTEMEN:                                                                    |
|                                                                                               |
| 1. [IC] Inventory Control                                                                     |
|    Username: ic_t001          Password: [ •••••••••••• ] [Eye (Reveal)] [Copy]                |
|    Wewenang: Live View Channel 1,2,3 & Playback Area Gudang                                   |
|-----------------------------------------------------------------------------------------------|
| 2. [EDP] IT Support (Administrator)                                                           |
|    Username: edp_admin        Password: [ •••••••••••• ] [Eye (Reveal)] [Copy]                |
|    Wewenang: Full Admin Access (Device Config & Firmware)                                     |
|-----------------------------------------------------------------------------------------------|
| 3. [SEC] Security Toko                                                                        |
|    Username: sec_guard        Password: [ •••••••••••• ] [Eye (Reveal)] [Copy]                |
|    Wewenang: Live View All Channel (No Playback/Config)                                       |
|-----------------------------------------------------------------------------------------------|
| 4. [OPS] Operasional Toko                                                                     |
|    Username: ops_spv          Password: [ •••••••••••• ] [Eye (Reveal)] [Copy]                |
|    Wewenang: Live View Area Sales & Antrean Kasir                                             |
|-----------------------------------------------------------------------------------------------|
| 5. [AUD] Internal Audit                                                                       |
|    Username: audit_loss       Password: [ •••••••••••• ] [Eye (Reveal)] [Copy]                |
|    Wewenang: Playback & Export Rekaman Semua Channel                                          |
+-----------------------------------------------------------------------------------------------+
| [Tombol: Catat Checklist DVR Ini]                                     [Tombol: Simpan Perubahan] |
+-----------------------------------------------------------------------------------------------+
```

---

## 3. Mobile Wireframe: Tampilan Responsif Smartphone Teknisi (375x812)

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
| IP: 10.10.1.200 : 80              |
| Model: Hikvision 8-Ch             |
+-----------------------------------+
| AKUN DEPARTEMEN SAYA (IC):        |
| User: ic_t001                     |
| Pass: [ •••••••• ] [Show] [Copy]  |
+-----------------------------------+
| FORM CHECKLIST CEPAT LAPANGAN:    |
| Ping DVR:         [ (x) ON  ( ) OFF ]
| Sinkron Jam NTP:  [ (x) Pas ( ) Beda]
| Status HDD:       [ Normal      v ]
| Fisik Kamera:     Working: [ 8 ]  |
|                   Broken:  [ 0 ]  |
| Catatan:                          |
| [ Semua view normal, debu dibersi]|
+-----------------------------------+
| [ >>> SUBMIT LAPORAN CEK <<< ]    |
+-----------------------------------+
| [Home]  [Scan QR]  [Cek]  [Profil]|
+-----------------------------------+
```