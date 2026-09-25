# CDAMS - CCTV DVR Asset & Access Management System

Sistem Manajemen Aset, Kredensial Akses 5 Departemen, dan Checklist Lapangan CCTV DVR berbasis Web Responsif (Desktop & Mobile) untuk jaringan ritel skala besar (~666 gerai/toko).

---

## 📌 Ringkasan Proyek

**CDAMS** dikembangkan untuk menggantikan pencatatan manual/spreadsheet inventaris CCTV DVR di seluruh jaringan gerai. Sistem ini mengontrol data perangkat fisik, memetakan secara baku 5 akun akses per DVR untuk masing-masing departemen internal, memfasilitasi inspeksi teknis lapangan secara *mobile-first*, serta merekam seluruh jejak aktivitas sensitif melalui sistem audit log *append-only*.

### 🚀 Fitur Unggulan Sistem
1. **Direktori Master Toko & Aset DVR (~666 Gerai):**
   - Mendukung pencatatan 1 hingga 2 unit DVR per toko (DVR 1 & DVR 2), dengan dukungan penambahan unit via flag `allow_extra_dvr`.
   - Pencatatan spesifikasi lengkap: Merk, Model Series, **Serial Number (SN)**, IP Address, HTTP Port, RTSP Port, Server SDK Port, Channel, Kapasitas Storage (TB), dan Lama Retensi Rekaman (Hari).
   - Fitur **Edit Toko** dan **Edit Detail DVR** via modal interaktif langsung dari halaman detail.
   - Quick Ping Test untuk verifikasi konektivitas IP DVR langsung dari antarmuka web.

2. **Standardisasi 5 Akun Departemen per DVR:**
   - Setiap unit DVR memiliki 5 slot akun terisolasi:
     1. **IC (Inventory Control):** Akses rekaman/live view area gudang & kasir.
     2. **EDP (IT Support):** Akses Administrator / full maintenance.
     3. **SPV (Supervisor Area):** Akses Live View area publik & perimeter.
     4. **DEV (Team Development):** Akses Live View area kasir & sales area.
     5. **AUD (Internal Audit):** Akses playback & export rekaman pembuktian fraud.
   - **Keamanan Kredensial:** Password dienkripsi dua arah dengan algoritma `AES-256-CBC` (Laravel Crypt). Password teks terbuka tidak pernah disimpan di database.
   - **Secure Reveal Timeout:** Fitur intip password dengan penghitung mundur otomatis (15 detik) yang langsung memicu pencatatan audit log `CREDENTIAL_REVEAL`.

3. **Checklist & Inspeksi Lapangan (Mobile-Friendly):**
   - Form inspeksi cepat untuk teknisi saat kunjungan ke toko: Ping status, deviasi jam RTC terhadap NTP server (indikator batas toleransi > 180 detik), kondisi harddisk (Normal/Error/Full/Unformatted), jumlah kamera normal/rusak, jenis jaringan (LAN/WAN), dan catatan lapangan.
   - **Sinkronisasi Real-Time:** Submit checklist otomatis memperbarui timestamp `last_check_at` pada unit DVR.
   - **Menu Rekapitulasi Checklist (`/checks`):** Menampilkan daftar riwayat checklist seluruh toko, lengkap dengan badge abnormalitas, nama teknisi pelaksana, dan waktu pelaksanaan terformat (`d M Y, H:i WIB`).

4. **Manajemen Pengguna & RBAC (`/users`):**
   - Role-Based Access Control lengkap:
     - `superadmin`: Akses penuh konfigurasi sistem, audit logs, user management, CRUD toko & DVR.
     - `technician`: Akses input checklist lapangan, inspeksi teknis, dan verifikasi perangkat.
     - `dept_operator`: Akses terisolasi khusus melihat kredensial akun departemennya sendiri.
     - `management`: Read-only monitoring dashboard dan statistik agregat.
   - Manajemen user terpadu: Tambah pengguna baru, ubah role, asosiasi departemen, reset password, dan aktivasi/nonaktifkan akun.

5. **Import & Ekspor Data Cerdas:**
   - **Template Resmi:** Unduhan template Excel/CSV yang sudah disesuaikan dengan format sistem melalui endpoint `/api/v1/stores/template`.
   - **Import Massal:** Upload spreadsheet master toko beserta spesifikasi DVR 1 dan DVR 2 (termasuk Serial Number).
   - **Proteksi Ekspor:** Deteksi otomatis data kosong (toast notification pencegah error) serta verifikasi One-Time Password (OTP) saat diakses melalui jaringan luar/WAN.

6. **UI/UX Modern & Dual Theme (Dark/Light):**
   - Dibangun dengan **Vue 3 SPA**, **Vite**, dan **Tailwind CSS v4**.
   - Tombol toggle Dark/Light Mode dengan persistensi otomatis di `localStorage`.
   - Navigasi responsif (Sidebar pada desktop dan Mobile-friendly bottom bar/cards).

7. **Deployment & Jaringan Siap Pakai:**
   - Konfigurasi `trustProxies(at: '*')` di Laravel 12 untuk mendukung arsitektur NAT / Port Forwarding (contoh: IP Public `202.46.148.102:8888` diteruskan ke IP Server LAN `192.168.25.50`).
   - Deteksi otomatis jaringan request (`LAN` vs `WAN`).

---

## 🛠️ Tech Stack

| Lapisan | Teknologi | Keterangan |
|---|---|---|
| **Backend Framework** | Laravel 12 (PHP 8.2+) | RESTful API, Sanctum Authentication, Eloquent ORM |
| **Frontend Framework** | Vue.js 3 (Composition API) | SPA dengan `<script setup>`, Vite Bundler |
| **State Management** | Pinia | Global Store untuk Auth, Theme, dan UI State |
| **Routing** | Vue Router 4 | Client-side routing dengan Navigation Guards & RBAC |
| **Styling & Icons** | Tailwind CSS v4 + Lucide Icons | Responsive Design, Dark & Light Mode |
| **Database** | MySQL 8.x / MariaDB | InnoDB Engine, Foreign Key Constraints, Indexing |
| **HTTP Client** | Axios | Interceptors untuk Bearer Token & Error Handling |
| **Testing** | PHPUnit / Pest | Automated Feature & Unit Tests (18 test suites passing) |

---

## 📋 Struktur Menu & Halaman Aplikasi

| Path Route | Tampilan (View) | Akses Role | Fungsi Utama |
|---|---|---|---|
| `/login` | `LoginView.vue` | Publik / Guest | Form autentikasi email & password |
| `/` | `DashboardView.vue` | Semua Role Terautentikasi | Ringkasan KPI, status online DVR, log aktivitas terkini |
| `/stores` | `StoresListView.vue` | Semua Role Terautentikasi | Direktori toko, filter wilayah, import/export, tambah toko |
| `/stores/:id` | `StoreDetailView.vue` | Semua Role Terautentikasi | Detail toko, info DVR, kartu kredensial 5 departemen, riwayat cek |
| `/checks` | `ChecksView.vue` | Semua Role Terautentikasi | Rekap checklist seluruh toko, filter temuan, riwayat inspeksi |
| `/users` | `UsersView.vue` | `superadmin` | Kelola akun pengguna sistem, role, departemen, status aktif |
| `/audit-logs` | `AuditLogsView.vue` | `superadmin` | Riwayat audit log tak terhapuskan (*append-only trail*) |

---

## ⚙️ Panduan Instalasi & Menjalankan Lokal

### 1. Kebutuhan Sistem
- PHP >= 8.2 (ekstensi: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath)
- Composer >= 2.x
- Node.js >= 18.x & NPM >= 9.x
- Database MySQL 8.x / MariaDB

### 2. Kloning & Pengaturan Dependensi
```bash
# Masuk ke direktori proyek
cd d:/laragon/www/MonitoringDvr

# Install dependensi PHP
composer install

# Install dependensi Node.js
npm install
```

### 3. Pengaturan Environment (`.env`)
Salin file `.env.example` menjadi `.env` jika belum ada:
```bash
cp .env.example .env
```
Sesuaikan konfigurasi koneksi database di file `.env`:
```ini
APP_NAME=CDAMS
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitoring_dvr
DB_USERNAME=root
DB_PASSWORD=
```

Generate application key:
```bash
php artisan key:generate
```

### 4. Migrasi Database & Seeding
Jalankan migrasi untuk membuat seluruh tabel dan masukkan data awal (departemen, akun persona, sampel toko & DVR):
```bash
php artisan migrate:fresh --seed
```

### 5. Kompilasi Frontend & Menjalankan Server
Jalankan server pengembangan Laravel dan Vite:

**Terminal 1 (Backend API):**
```bash
php artisan serve --port=8000
```

**Terminal 2 (Frontend Hot-Reload):**
```bash
npm run dev
```

Atau untuk build produksi:
```bash
npm run build
```

---

## 👥 Akun Persona Bawaan (Default Seeders)

Semua akun menggunakan kata sandi default: `password`

| Nama Pengguna | Email | Peran (Role) | Departemen Terkait |
|---|---|---|---|
| **Super Admin EDP** | `admin@indomaret.test` | `superadmin` | *All Access* |
| **Syahdat** | `teknisi@indomaret.test` | `technician` | Lapangan / Maintenance |
| **Aris** | `ic@indomaret.test` | `technician` | IC (Inventory Control) |
| **Jurit** | `spv@indomaret.test` | `dept_operator` | SPV (Supervisor Area) |
| **ERO** | `dev@indomaret.test` | `dept_operator` | DEV (Development) |
| **Cia** | `cia@indomaret.test` | `dept_operator` | AUD (Internal Audit) |
| **SYI** | `manager@indomaret.test` | `dept_operator` | Management / Operations |

---

## 🧪 Pengujian Otomatis (Automated Testing)

Proyek ini dilengkapi dengan rangkaian pengujian fitur API lengkap yang memvalidasi integritas autentikasi, RBAC, CRUD toko, dekripsi password, enkripsi kredensial, audit log, dan ekspor/impor.

Jalankan test suite menggunakan Artisan:
```bash
php artisan test
```

Hasil verifikasi:
```text
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\AuthTest
PASS  Tests\Feature\CdamsApiTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\UserControllerTest

Tests:    18 passed (149 assertions)
Duration: ~1.5s
```

---

## 🌐 Arsitektur Deployment & Port Forwarding

Sistem dirancang untuk beroperasi di lingkungan Head Office (LAN) maupun diakses melalui jaringan luar / WAN:
* **Server Lokal (LAN):** `http://192.168.25.50`
* **Akses Publik (Port Forwarding):** `http://202.46.148.102:8888`
* **Konfigurasi Reverse Proxy & Trusted Proxies:**
  File `bootstrap/app.php` telah dikonfigurasi dengan `$middleware->trustProxies(at: '*')`, sehingga header `X-Forwarded-For` dan `X-Forwarded-Proto` dari router/firewall diteruskan secara aman ke aplikasi untuk pencatatan IP klien yang akurat pada audit log.

---

## 📄 Lisensi
Sistem ini bersifat hak milik internal (Proprietary Software) untuk pengelolaan operasional CCTV dan aset toko ritel.
