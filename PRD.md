# Product Requirement Document (PRD)

| Metadata | Keterangan |
|---|---|
| **Nama Proyek** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Versi Dokumen** | 1.2.0 |
| **Tech Stack** | Backend: PHP 8.2+ (Laravel 12 REST API), Frontend: Vue.js 3 SPA (Vite + Tailwind CSS v4 + Pinia), Database: MySQL 8.x |
| **Target Skala** | ~666 Toko (1-2 DVR per toko, total estimasi 666 – 1.332 DVR, 3.330 – 6.660 akun akses terdata) |
| **Akses Jaringan** | LAN (Head Office/Cabang) & WAN (Jaringan Toko / VPN / Public Port Forwarding) |

---

## 1. Ringkasan Eksekutif & Latar Belakang

Perusahaan ritel dengan jaringan toko berskala besar (~666 gerai) membutuhkan platform terpusat untuk memantau inventaris perangkat CCTV DVR, status operasional berkala, serta mengontrol minimal 5 slot akun spesifik yang dialokasikan ke masing-masing departemen internal:
1. **Inventory Control (IC):** Pengawasan area gudang & stok barang.
2. **EDP / IT Support:** Pemeliharaan sistem, firmware, dan konfigurasi jaringan.
3. **Supervisor Area (SPV):** Pengawasan operasional area publik & keamanan toko.
4. **Team Development (DEV):** Monitoring area sales & kenyamanan pelanggan.
5. **Internal Audit (AUD):** Pemeriksaan transaksi kasir & audit investigasi fraud.

Sistem CDAMS dirancang berbasis web SPA modern (responsif desktop & mobile) untuk:
* Menggantikan pencatatan spreadsheet manual menjadi database terpusat yang aman dan terstruktur.
* Menstandarisasi 5 slot akun kredensial per unit DVR agar tidak terjadi tumpang tindih wewenang atau kebocoran kata sandi.
* Memfasilitasi form inspeksi teknis lapangan (*mobile-first*) yang dapat diisi teknisi saat melakukan kunjungan pemeliharaan.
* Menjamin keamanan data kredensial dengan enkripsi dua arah simetris (`AES-256-CBC`) dan audit trail tak terhapuskan (*append-only*).
* Menyediakan fitur pencatatan Serial Number (SN) perangkat dan rekap temuan masalah teknis secara real-time.

---

## 2. Tujuan & Key Performance Indicators (KPI)

### 2.1 Tujuan Produk
1. **Sentralisasi Data Aset & Serial Number:** Menyimpan data lengkap ~666 toko beserta unit DVR 1 dan DVR 2 secara terstruktur.
2. **Standardisasi Akses Departemen:** Memastikan setiap DVR memiliki data akun kredensial yang baku untuk 5 departemen terkait.
3. **Audit Cepat di Lapangan:** Menyediakan form inspeksi mobile-friendly yang ringan diakses melalui koneksi WAN toko.
4. **Keamanan Kredensial:** Memastikan kata sandi perangkat DVR terenkripsi aman, dengan mekanisme *reveal password* berbatas waktu (15 detik) yang memicu pencatatan audit log.
5. **Transparansi Riwayat Pemeriksaan:** Menyediakan rekapitulasi checklist lapangan terpusat untuk mendeteksi abnormalitas (NTP tidak sinkron, HDD error, kamera rusak).

### 2.2 Target KPI
* Waktu pencarian status perangkat per kode toko < 1 detik.
* 100% data toko (666 gerai) dan DVR terkait berhasil terpetakan dalam 30 hari pasca peluncuran.
* Pengurangan insiden akun DVR terkunci/salah password lintas departemen sebesar 80%.
* Integritas log audit 100% terekam tanpa ada celah manipulasi (*non-repudiation*).

---

## 3. Arsitektur Teknis & Topologi Jaringan

### 3.1 Tech Stack Detail
* **Backend:** PHP 8.2+ / Laravel 12.x (REST API, Eloquent ORM, Gates & Policies, Sanctum Bearer Token, Trusted Proxies).
* **Frontend:** Vue.js 3 SPA (Composition API `<script setup>`, Vite, Pinia State Management, Vue Router 4, Tailwind CSS v4, Lucide Icons).
* **Database:** MySQL 8.x / MariaDB (InnoDB engine, indexing teroptimasi untuk query pencarian cepat).
* **Keamanan Kredensial:** Enkripsi simetris `AES-256-CBC` via Laravel `Crypt::encryptString()` dan `Crypt::decryptString()`.

### 3.2 Topologi Jaringan & Akses Server

```
[ Pengguna LAN: Head Office (192.168.25.x) ] ──┐
                                              ├──> [ Gateway Router / Firewall ]
[ Pengguna WAN / Internet: 202.46.148.102:8888] ──┤        │ (Port Forwarding: 8888 -> 8000)
                                                       ▼
                                          [ Server Aplikasi (LAN: 192.168.25.50:8000) ]
                                          ├── Laravel 12 Backend API
                                          ├── Vue 3 SPA Bundled Assets
                                          └── MySQL Database (Localhost:3306)
```

* **Akses LAN (Head Office):**
  * Akses langsung melalui IP server lokal: `http://192.168.25.50:8000`
  * Akses tanpa pembatasan bandwidth untuk ekspor massal, user management, dan audit trail.
* **Akses WAN (Jaringan Toko & Publik via Port Forwarding):**
  * Akses publik dialihkan melalui port forwarding: `http://202.46.148.102:8888`
  * Dikonfigurasi dengan `trustProxies(at: '*')` sehingga header `X-Forwarded-For` dan `X-Forwarded-Proto` dari router/firewall diteruskan dengan aman, memastikan deteksi IP klien akurat.
  * Fitur ekspor data massal pada mode WAN dilindungi verifikasi OTP.

---

## 4. User Personas & Role-Based Access Control (RBAC)

| Peran (Role) | Target Pengguna | Hak Akses Utama |
|---|---|---|
| **Super Admin (`superadmin`)** | Tim EDP Head Office | Akses penuh CRUD Toko, DVR, Manajemen Akun Departemen, User Management & RBAC (`/users`), Audit Logs (`/audit-logs`), dan Ekspor Data Massal. |
| **Field Technician (`technician`)** | Teknisi Lapangan Cabang | Input checklist lapangan harian/bulanan via mobile, view IP & port DVR, eksekusi ping test, serta melihat rekapitulasi checklist (`/checks`). |
| **Department Operator (`dept_operator`)** | Tim IC, SPV, DEV, AUD | Akses terisolasi: hanya dapat melihat direktori toko dan kredensial/akun yang dialokasikan khusus untuk divisinya (misal: user IC hanya bisa melihat akun slot IC). |
| **Management (`management`)** | Manajer Operasional / Wilayah | Read-only dashboard agregat, laporan statistik DVR online/offline, dan rekapitulasi riwayat checklist toko. |

---

## 5. Fitur Utama & Kebutuhan Fungsional

### 5.1 Manajemen Data Toko (~666 Toko)
* **CRUD Toko:** Input dan update data Kode Toko (unik, contoh: `T001`), Nama Toko, Wilayah/Region, Alamat, Subnet IP Toko, Kontak Person, dan Telepon.
* **Fitur Pencarian & Filter:** Filter berdasarkan Wilayah, Kode Toko (Global Search modal `Ctrl + K`), atau status toko (`Active`, `Renovation`, `Closed`).
* **Modal Edit Toko:** Memungkinkan modifikasi data toko secara langsung dari halaman `StoreDetailView.vue`.
* **Proteksi Toko Tutup:** Toko dengan status `Closed` secara otomatis menonaktifkan akun dan checklist operasional.

### 5.2 Manajemen Perangkat DVR (1 atau 2 DVR per Toko)
* **Relasi Toko-DVR:** Setiap toko default dialokasikan maksimal 2 unit DVR (DVR 1 dan DVR 2). Unit ke-3 diizinkan hanya jika flag `allow_extra_dvr = true`.
* **Identitas & Spesifikasi Perangkat:**
  - Label Unit (`DVR 1 - Area Toko & Kasir`, `DVR 2 - Area Gudang & Loading`).
  - Merk (Hikvision, Dahua, dll.), Model Series, dan **Serial Number (SN)** unik.
  - Alamat IP DVR, HTTP Web Port, RTSP Video Stream Port, dan Server SDK Port.
  - Jumlah Channel (4, 8, 16, 32), Kapasitas Storage (TB), dan Lama Retensi Rekaman (Hari).
* **Modal Edit Detail DVR:** Memungkinkan teknisi/admin memperbarui Serial Number, IP Address, port, kapasitas storage, dan retensi hari langsung dari halaman detail toko.
* **Quick Ping Test:** Verifikasi status koneksi IP DVR langsung dari kartu DVR.

### 5.3 Manajemen 5 Akun Departemen per DVR
* **Inisialisasi Otomatis:** Setiap kali record DVR dibuat, sistem secara otomatis mengenerate 5 slot akun departemen:
  - **Slot 1 (IC):** Wewenang *Live View & Playback* area gudang/kasir.
  - **Slot 2 (EDP):** Wewenang *Administrator / Full Access*.
  - **Slot 3 (SPV):** Wewenang *Live View Only* area publik & perimeter.
  - **Slot 4 (DEV):** Wewenang *Live View* area sales & kasir.
  - **Slot 5 (AUD):** Wewenang *Playback & Export* semua channel untuk audit fraud.
* **Enkripsi Kredensial:** Password akun DVR tersimpan terenkripsi simetris menggunakan `AES-256-CBC` via Laravel `Crypt`.
* **Secure Reveal Password:**
  - Tombol mata (*reveal*) pada kartu akun membuka password plaintext selama **15 detik**.
  - Dilengkapi progress bar / countdown timer otomatis.
  - Setiap aksi reveal dicatat ke dalam `audit_logs` dengan aksi `CREDENTIAL_REVEAL`.
* **Isolasi Role:** Pengguna dengan role `dept_operator` hanya dapat melihat kartu akun miliknya sendiri; kartu departemen lain otomatis disembunyikan.

### 5.4 Form & Rekapitulasi Checklist Lapangan
* **Inspeksi Mobile-First:**
  - Status Ping DVR (Online / Offline).
  - Verifikasi Sinkronisasi Waktu Jam DVR vs NTP Server (indikator deviasi waktu dalam satuan detik; jika selisih > 180 detik ditandai sebagai `Time Out of Sync`).
  - Kondisi Fisik Harddisk (`Normal`, `Error`, `Unformatted`, `Full`).
  - Jumlah kamera berfungsi (*working*) dan kamera bermasalah (*broken*).
  - Estimasi hari rekaman tersedia (*retention days*).
  - Jenis jaringan saat pengecekan (`LAN` / `WAN`) dan catatan temuan teknisi.
* **Sinkronisasi Real-Time:**
  - Saat checklist berhasil dikirim, kolom `dvrs.last_check_at` otomatis terupdate.
  - Halaman `StoreDetailView.vue` langsung menampilkan badge waktu checklist terakhir beserta nama teknisi pemeriksa.
* **Halaman Rekapitulasi Checklist (`/checks`):**
  - Menampilkan seluruh riwayat pemeriksaan dari seluruh toko.
  - Filter pencarian berdasarkan nama toko, kode toko, status temuan abnormal, dan rentang tanggal.
  - Badge indikator temuan cepat (NTP Selisih >180s, HDD Error, Kamera Rusak).
  - Tombol modal detail untuk melihat rincian catatan lengkap checklist.

### 5.5 Manajemen Pengguna & Hak Akses (`/users`)
* Khusus untuk Super Admin:
  - Melihat seluruh daftar pengguna sistem beserta status aktif dan waktu registrasi.
  - Menambah pengguna baru dengan pilihan peran (`superadmin`, `technician`, `dept_operator`, `management`).
  - Memilih asosiasi departemen bagi pengguna role `dept_operator`.
  - Mengubah profil, email, nomor HP, role, dan kata sandi pengguna.
  - Mengaktifkan atau menonaktifkan (*toggle active*) akun pengguna secara instan.
  - Seluruh mutasi pengguna memicu audit log (`USER_CREATED`, `USER_UPDATED`, `USER_DELETED`).

### 5.6 Impor & Ekspor Data Massal
* **Download Template Resmi (`GET /api/v1/stores/template`):**
  - Menyediakan file template Excel/CSV siap pakai dengan struktur kolom baku (termasuk kolom `dvr1_serial_number` dan `dvr2_serial_number`).
* **Modal Import Data Toko (`StoreImportModal.vue`):**
  - Validasi file ekstensi `.csv`, `.xlsx`, `.xls` hingga ukuran 10MB.
  - Memproses batch import toko dan membuat unit DVR serta 5 akun departemen secara otomatis.
* **Ekspor Data Terproteksi:**
  - Ekspor langsung dalam bentuk spreadsheet Excel terformat.
  - Validasi data kosong (*empty dataset guard*): Jika filter menghasilkan 0 data, sistem menampilkan notifikasi peringatan tanpa error redirect.
  - Deteksi jaringan: Jika diakses melalui WAN, sistem mewajibkan verifikasi OTP (`/stores/export-otp`) sebelum link unduhan dibuka.

### 5.7 Audit Trail & Keamanan (*Non-Repudiation*)
* Tabel `audit_logs` bersifat *append-only* (tidak ada fitur edit atau delete).
* Mencatat parameter esensial: ID Pengguna, Nama Action, Target Entitas, Departemen, Jenis Jaringan (`LAN`/`WAN`), IP Address Klien, User Agent, dan JSON Diff (`old_values` vs `new_values`).
* Akses tampilan audit log (`/audit-logs`) hanya terbuka bagi Super Admin.

---

## 6. Antarmuka Pengguna & Fitur Estetika

* **Dual Theme Engine (Dark & Light Mode):**
  - Dukungan penuh mode gelap (*Sleek Dark Mode*) dan terang (*Clean Daylight*).
  - Toggle theme berada di navbar atas dan tersimpan secara persisten di `localStorage`.
  - Menggunakan utility class Tailwind CSS v4 untuk transisi warna yang halus.
* **Global Search Modal (`Ctrl + K`):**
  - Navigasi instan ke toko mana pun dengan mengetik kode toko, nama toko, atau alamat.
* **Kerapatan Informasi Desktop & Ketanggapan Mobile:**
  - Tampilan desktop memaksimalkan kepadatan data (tabel master toko dengan multi-filter dan badge status).
  - Tampilan mobile memprioritaskan kartu terlipat (*card accordion*) dan form checklist yang mudah ditekan dengan satu tangan.
