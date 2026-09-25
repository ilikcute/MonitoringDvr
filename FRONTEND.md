# UI/UX Specification

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Framework** | Vue.js 3 + Tailwind CSS |
| **Design Approach** | Responsive Adaptive (Desktop Data-Dense, Mobile Action-First) |

---

## 1. Prinsip Desain & Palet Warna

* **Primary (EDP / Industrial Blue):** `#1E40AF` (Tailwind `blue-800`) – Elemen navigasi, header, aksi primer.
* **Accent (Action/Success):** `#059669` (Tailwind `emerald-600`) – Status Online, Akun Aktif, Tombol Simpan.
* **Warning / Alert:** `#D97706` (Tailwind `amber-600`) – Time Out of Sync, Overdue Check.
* **Danger:** `#DC2626` (Tailwind `red-600`) – Offline, HDD Error, Hapus Data.
* **Background Surface:** `#F8FAFC` (Tailwind `slate-50`) untuk canvas, `#FFFFFF` untuk cards/modals.

---

## 2. Desain Layout Berdasarkan Breakpoint

### 2.1 Desktop Layout ($\ge 1024\text{px}$)
* **Navigasi:** Persistent Collapsible Sidebar di sebelah kiri (Dashboard, Master Toko, Status DVR, Checklist, Audit Log, User Management).
* **Header Bar:** Menampilkan Status Mode Jaringan (`LAN Head Office` / `WAN Toko`), Search Global Bar (`Ctrl + K`), dan User Profile.
* **Kerapatan Informasi:** Menggunakan **Dense Data Table** dengan kolom:
  `Kode Toko | Nama Toko | Region | DVR 1 (Status & IP) | DVR 2 (Status & IP) | Akun Ready | Aksi`.
* **Ekspor & Filter Cepat:** Komponen multi-dropdown untuk Area, Status DVR (All/Online/Offline), dan tombol `Export Excel`.

### 2.2 Mobile Layout ($< 768\text{px}$) – Teknisi Lapangan / Mobile View
* **Navigasi:** Bottom Navigation Bar dengan 4 ikon utama: `Dashboard`, `Pencarian Toko`, `Form Cek`, `Profil`.
* **Pencarian Cepat:** Floating Top Search Bar dengan fitur autofocus untuk mengetik Kode Toko (misal: `T214`) atau scan QR Toko.
* **Tampilan Data Berbasis Kartu (Card-Based):**
  * Tidak menggunakan horizontal scrolling table.
  * Setiap toko disajikan dalam kartu terlipat (*accordion card*):
    * Header kartu: `[Kode Toko] - Nama Toko` + Badge Status (`2/2 Online`).
    * Isi kartu: Tab segmented `[DVR 1]` dan `[DVR 2]`.
    * Tombol aksi besar: `[Lihat Kredensial]` dan `[Mulai Checklist]`.

---

## 3. Komponen Khusus

### 3.1 Komponen Kartu Akun Departemen (Secure Credential Card)
* Menampilkan 5 kartu vertikal sesuai departemen:
  * Icon badge: `IC` (Kuning), `EDP` (Biru), `Security` (Merah), `Ops` (Hijau), `Audit` (Ungu).
  * Field: `Username` (teks biasa), `Password` (awalnya disembunyikan dalam karakter bullet `••••••••`).
  * Interaksi:
    * Klik icon mata: Mengirim request dekripsi ke API internal; password terbuka selama 10 detik lalu kembali tersamarkan.
    * Tombol `Copy`: Menyalin langsung ke clipboard perangkat.

### 3.2 Form Checklist Cepat Mobile (Fast Inspection Wizard)
* Didesain dengan tombol sakelar (*toggle switch*) berukuran ramah sentuhan (touch target minimum $48\times 48\text{px}$):
  * `Ping Online?` [Ya] / [Tidak]
  * `Kondisi HDD:` [Normal] / [Error]
  * `Kamera Rusak:` Input counter minus/plus `[-] 0 [+]`
  * `Catatan:` Input suara (speech-to-text bawaan browser) atau ketik ringkas.