# UI/UX Specification (Frontend Architecture)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Framework** | Vue.js 3 (Composition API `<script setup>`) + Vite |
| **State Management** | Pinia Global Store |
| **Styling** | Tailwind CSS v4 + Lucide Icons |
| **Tema** | Dual Theme Engine (Light Mode & Dark Mode dengan persistensi `localStorage`) |
| **Versi Dokumen** | 1.2.0 |

---

## 1. Prinsip Desain & Sistem Warna

* **Primary (Industrial Corporate Blue):** `#1E40AF` (Tailwind `blue-800`) – Header, tombol aksi primer, tab aktif.
* **Success / Online Accent:** `#059669` (Tailwind `emerald-600`) – Status DVR Online, checklist normal, indikator berhasil.
* **Warning / Alert:** `#D97706` (Tailwind `amber-600`) – NTP Out of Sync (> 180s), Check Overdue, DVR Degraded.
* **Danger / Offline:** `#DC2626` (Tailwind `red-600`) – DVR Offline, HDD Error, Kamera Rusak, Tombol Hapus.
* **Canvas Terang (Light Mode):** Background `#F8FAFC` (`slate-50`), Kartu/Panel `#FFFFFF`, Border `#E2E8F0` (`slate-200`).
* **Canvas Gelap (Dark Mode):** Background `#0F172A` (`slate-900`), Kartu/Panel `#1E293B` (`slate-800`), Border `#334155` (`slate-700`).

---

## 2. Fitur Unggulan Antarmuka (UI Features)

### 2.1 Dual Theme Engine (Dark & Light Mode)
* Dikelola melalui state reaktif pada Pinia store (`stores/theme.js`).
* Tersimpan otomatis di `localStorage` peramban pengguna.
* Menggunakan selector class `.dark` pada elemen root `<html>`, diselaraskan dengan Tailwind CSS v4 untuk transisi warna yang mulus dan nyaman di mata teknisi lapangan saat malam hari maupun siang hari.

### 2.2 Global Search Modal (`Ctrl + K`)
* Dapat diaktifkan melalui shortcut keyboard `Ctrl + K` atau klik search bar pada navbar atas.
* Pencarian cepat tanpa jeda (*instant search*) berdasarkan Kode Toko, Nama Toko, atau Wilayah.
* Navigasi langsung ke halaman detail toko yang dipilih.

### 2.3 Deteksi Jaringan Real-Time (LAN vs WAN Badge)
* Header bar secara otomatis menampilkan badge mode koneksi:
  * `LAN - Head Office` (Hijau) saat diakses dari jaringan kantor pusat.
  * `WAN - Toko / Publik` (Amber/Biru) saat diakses melalui internet / port forwarding publik.

---

## 3. Direktori Halaman (Views Architecture)

### 3.1 `LoginView.vue` (`/login`)
* Halaman autentikasi tunggal dengan branding korporat CDAMS.
* Form input Email & Password dengan validasi sisi klien dan penanganan error dari API.
* Pengalihan otomatis (*navigation guard*) sesuai status autentikasi.

### 3.2 `DashboardView.vue` (`/`)
* Kartu metrik KPI utama: Total Toko, Total Unit DVR, DVR Online, DVR Offline, dan DVR Overdue Pemeriksaan.
* Indikator status jaringan aktif (`LAN` / `WAN`).
* Tabel riwayat aktivitas audit log terkini (*recent activities feed*).

### 3.3 `StoresListView.vue` (`/stores`)
* **Data-Dense Table:** Menampilkan daftar toko dengan kolom: Kode Toko, Nama Toko, Wilayah, Status DVR 1, Status DVR 2, Kesiapan Akun, dan Aksi.
* **Filter Bar:** Pencarian teks, filter wilayah (*region*), dan filter status toko.
* **Aksi Massal:**
  - Tombol **Export Excel** dengan proteksi dataset kosong (*empty toast notification*) dan tantangan OTP jika diakses via WAN.
  - Tombol **Import Excel/CSV** yang membuka modal import dengan validasi file dan link unduhan template resmi.
  - Tombol **Tambah Toko** untuk mendaftarkan gerai baru.

### 3.4 `StoreDetailView.vue` (`/stores/:id`)
* **Header & Info Toko:** Kode toko, nama gerai, wilayah, subnet IP, kontak person, nomor telepon, dan status gerai.
* **Aksi Toko:** Tombol **Edit Toko** membuka modal pembaruan informasi gerai.
* **Kartu DVR Interaktif:**
  - Menampilkan Label DVR, Merk, Model, **Serial Number (SN)**, IP Address & Port, serta kapasitas storage/retensi.
  - Tombol **Edit Detail DVR** untuk memperbarui serial number, IP, port, atau kapasitas tanpa menghapus kredensial.
  - Tombol **Quick Ping Test** untuk memeriksa respon jaringan perangkat secara instan.
  - Badge waktu checklist terakhir terformat (`d M Y, H:i WIB`) beserta nama teknisi pelaksana.
* **Grid 5 Akun Departemen (`CredentialCard.vue`):**
  - Kartu terpisah untuk: `IC`, `EDP`, `SPV`, `DEV`, `AUD`.
  - Tombol intip password (*reveal*) dengan countdown otomatis 15 detik dan log audit otomatis.
  - Tombol salin kredensial (*copy to clipboard*).
  - Isolasi wewenang: Operator hanya melihat akun divisinya sendiri; Super Admin melihat seluruh 5 akun.
* **Riwayat Checklist Lapangan:** Daftar inspeksi terdahulu beserta tombol **Mulai Checklist** untuk membuka formulir inspeksi baru.

### 3.5 `ChecksView.vue` (`/checks`)
* Halaman rekapitulasi checklist lapangan seluruh toko.
* Filter pencarian toko, filter temuan abnormal (*abnormal only*: deviasi jam NTP > 180s, HDD error, atau kamera rusak).
* Tabel rekapitulasi lengkap dengan kolom: Waktu Pemeriksaan (`d M Y, H:i WIB`), Toko, DVR, Teknisi Pelaksana, Status Ping, Sinkronisasi Jam, Kondisi HDD, Kamera Normal/Rusak, dan Tombol **Detail**.
* Modal detail checklist untuk meninjau catatan lengkap dan parameter teknis hasil inspeksi lapangan.

### 3.6 `UsersView.vue` (`/users`)
* Khusus untuk Super Admin:
  - Tabel manajemen akun pengguna sistem.
  - Filter pencarian nama/email dan filter role.
  - Badge role warna-warni (`superadmin`, `technician`, `dept_operator`, `management`).
  - Modal **Tambah / Edit Pengguna** dengan pemilihan role, asosiasi departemen, dan reset kata sandi.
  - Switch aktif/nonaktif akun instan.

### 3.7 `AuditLogsView.vue` (`/audit-logs`)
* Khusus untuk Super Admin:
  - Tabel pencatatan jejak audit sistem (*append-only*).
  - Filter jenis aksi (`CREDENTIAL_REVEAL`, `DVR_IP_CHANGED`, `USER_CREATED`, dll.), tipe jaringan (`LAN`/`WAN`), dan rentang waktu.
  - Modal peninjau JSON diff nilai lama (*old values*) versus nilai baru (*new values*).

---

## 4. Komponen Modular (`resources/js/components/`)

1. **`ChecklistFormModal.vue`:** Modal formulir inspeksi lapangan ramah sentuhan (touch-friendly) untuk input cepat teknisi.
2. **`CredentialCard.vue`:** Kartu kredensial departemen dengan fitur reveal timer 15 detik, copy clipboard, dan edit password.
3. **`StoreImportModal.vue`:** Modal upload berkas spreadsheet dengan link unduh template resmi dan progress bar status proses.
4. **`ExportOtpModal.vue`:** Modal verifikasi OTP untuk otorisasi ekspor data massal ketika diakses dari jaringan WAN.
5. **`GlobalSearchModal.vue`:** Modal pencarian cepat global dengan shortcut `Ctrl + K`.
6. **`PingTestButton.vue`:** Tombol utilitas asynchronous untuk verifikasi ping IP DVR secara langsung dengan status indikator visual.