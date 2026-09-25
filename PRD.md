# Product Requirement Document (PRD)

| Metadata | Keterangan |
|---|---|
| **Nama Proyek** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Versi Dokumen** | 1.1.0 |
| **Tech Stack** | Backend: PHP (Laravel 13+), Frontend: Vue.js 3 (REST API), Database: MySQL 8.x |
| **Target Skala** | ~666 Toko (1-2 DVR per toko, total estimasi 666 – 1.332 DVR, 3.330 – 6.660 akun akses terdata) |
| **Akses Jaringan** | LAN (Head Office/Cabang) & WAN (Jaringan Toko / VPN Perusahaan) |

---

## 1. Ringkasan Eksekutif & Latar Belakang

Perusahaan ritel dengan jaringan toko berskala besar (~666 gerai) membutuhkan platform terpusat untuk memantau inventaris perangkat CCTV DVR, status operasional berkala, serta mengontrol minimal 5 slot akun spesifik yang dialokasikan ke masing-masing departemen internal (Inventory Control/IC, EDP/IT, Security, Operasional Toko, dan Internal Audit).

Sistem ini dirancang berbasis web yang responsif (desktop & mobile) untuk mempermudah:
* Manajemen data aset perangkat secara akurat.
* Standardisasi pemetaan 5 akun per unit DVR agar tidak terjadi bentrok wewenang atau kebocoran kredensial.
* Checklist dan audit kondisi teknis (koneksi, harddisk, sinkronisasi jam) yang dapat dilakukan teknisi di lapangan menggunakan perangkat *mobile* saat berkunjung ke toko.
* update data saat melakukan pengecekan dan pergantian password akun sesuai kewenangan

---

## 2. Tujuan & Key Performance Indicators (KPI)

### 2.1 Tujuan Produk
1. **Sentralisasi Data Aset:** Menggantikan pencatatan manual berbasis spreadsheet menjadi database terstruktur MySQL.
2. **Standardisasi Akses Departemen:** Memastikan setiap DVR memiliki data akun kredensial yang rapi untuk 5 departemen berwenang.
3. **Audit Cepat di Lapangan:** Menyediakan form inspeksi mobile-friendly yang ringan diakses melalui jaringan WAN toko.
4. **Keamanan Kredensial:** Memastikan kata sandi perangkat DVR tersimpan terenkripsi secara dua arah (reversible encryption) dengan akses berbasis hak istimewa (RBAC).

### 2.2 Target KPI
* Waktu pencarian status perangkat per kode toko < 1 detik.
* 100% data toko (666 gerai) dan DVR terkait berhasil terpetakan dalam 30 hari pasca peluncuran.
* Pengurangan insiden akun DVR terkunci/salah password lintas departemen sebesar 80%.

---

## 3. Arsitektur Teknis & Topologi Jaringan

### 3.1 Tech Stack Detail
* **Backend:** PHP 11 / Laravel 11.x (Eloquent ORM, Gates & Policies, Sanctum/Breeze, Scheduled Tasks).
* **Frontend:** Vue.js 3 (Script Setup, Pinia State Management, Tailwind CSS untuk UI responsif, Axios / Inertia.js).
* **Database:** MySQL 8.x (InnoDB engine, indexing teroptimasi untuk query pencarian cepat).
* **Web Server & Reverse Proxy:** Nginx, PHP-FPM.

### 3.2 Akses Jaringan (LAN & WAN)

```
[ Pengguna LAN: Head Office / Data Center ] ──┐
                                              ├──> [ Nginx Reverse Proxy / SSL (Port 443) ]
[ Pengguna WAN: Toko / Laptop Teknisi Lapangan] ──┤        │
  (via SD-WAN / IPsec VPN / Subnet Toko)      ┘        ▼
                                              [ Laravel Backend (PHP-FPM) ]
                                                       │
                                              [ Database MySQL 8.0 ]
```

* **Akses LAN (Head Office):**
  * Akses langsung melalui IP privat / domain internal (misal: `http://cdams.corp.local` atau IP VLAN HO).
  * Kecepatan penuh untuk ekspor data massal, monitoring dashboard analitik, dan manajemen *user role*.
* **Akses WAN (Jaringan Toko & Lapangan):**
  * Server aplikasi di-publish melalui gerbang jaringan internal perusahaan (SD-WAN atau VPN kantor cabang).
  * Payload halaman dioptimalkan (Vite bundling, aset terkompresi Gzip/Brotli) agar antarmuka mobile tetap responsif pada koneksi WAN toko dengan bandwidth terbatas.
  * Proteksi firewall: Hanya menerima blok IP/Subnet jaringan toko dan Head Office (IP Whitelisting).

---

## 4. User Personas & Role-Based Access Control (RBAC)

| Peran (Role) | Target Pengguna | Hak Akses Utama |
|---|---|---|
| **Super Admin / EDP Core** | Tim EDP Head Office | Akses penuh CRUD Toko, DVR, Manajemen Master Akun, Konfigurasi Sistem, dan Audit Log. |
| **EDP Field / Teknisi Lapangan** | Teknisi Maintenance Cabang | Input hasil checklist harian/bulanan (mobile), update status konektivitas, cek port & IP DVR. |
| **Department Operator** | Tim IC, Security, Ops, Audit | Hanya dapat melihat daftar toko dan kredensial/akun yang dialokasikan khusus untuk divisinya (tidak bisa melihat akun divisi lain). |
| **Management / Viewer** | Manajer Operasional | Read-only dashboard agregat, laporan statistik DVR online/offline, dan riwayat kerusakan. |

---

## 5. Fitur Utama & Kebutuhan Fungsional

### 5.1 Manajemen Data Toko (~666 Toko)
* **CRUD Toko:** Input Kode Toko (unik, contoh: `T001`), Nama Toko, Wilayah/Region, Alamat, Subnet IP Toko, dan Kontak Person Toko.
* **Fitur Pencarian & Filter:** Filter berdasarkan Wilayah, Kode Toko, atau jumlah DVR yang aktif.
* **Import/Export Data:** Dukungan import CSV/Excel untuk inisialisasi awal 666 toko dan ekspor status terkini.

### 5.2 Manajemen Perangkat DVR (1 atau 2 DVR per Toko)
* **Relasi Toko-DVR:** Setiap toko dapat memiliki minimal 1 dan maksimal 2 (atau lebih jika ekspansi) unit DVR.
* **Identitas DVR:** Label Perangkat (`DVR 1 - Area Kasir & Sales`, `DVR 2 - Area Gudang & Loading`), Merk (Hikvision, Dahua, dll.), Model/Tipe, Serial Number, Jumlah Channel (4/8/16/32).
* **Konfigurasi Jaringan DVR:** IP Address DVR, Port Web/HTTP, Port RTSP, Port Server/Client, Status Koneksi (`Online`, `Offline`, `Trouble`).

### 5.3 Manajemen minimal 5 Akun Departemen per DVR
* **Setiap 1 unit DVR memiliki slot tetap untuk 5 akun departemen:

### 5.4 Manajemen Identitas DVR 
* **Setiap kunjungan tehnisi akan dilakukan pergantian berkala username dan password serta pemastian setting port rtsp, port http dan port media 


