# Business Rules Specification (BRD-RULE)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Versi Dokumen** | 1.2.0 |
| **Status** | Approved for Production |

---

## 1. Aturan Entitas Toko (Store Rules)

* **BR-STR-001 (Unique Store Code):**
  * Kode toko (`store_code`) bersifat unik secara global (contoh: `T001` s/d `T666`).
  * Kode toko menjadi pengenal utama pencarian global dan integrasi antar modul.
* **BR-STR-002 (Kapasitas DVR & Spesifikasi Fisik):**
  * Setiap toko wajib memiliki minimal **1 unit DVR** aktif saat status toko `Active`.
  * Batas default kapasitas unit per toko adalah **maksimal 2 unit DVR** (DVR 1 dan DVR 2).
  * Penambahan unit ke-3 atau lebih hanya diizinkan melalui *override flag* khusus (`allow_extra_dvr = true`).
  * Setiap unit DVR wajib mencatat identitas perangkat fisik: **Serial Number (SN)** unik untuk tracking garansi, Merk, Model Series, Alamat IP, Port Jaringan, Kapasitas Storage (TB), dan Lama Retensi Rekaman (Hari).
* **BR-STR-003 (Lifecycle Status Toko):**
  * Status toko: `Active`, `Renovation`, `Closed`.
  * Toko berstatus `Closed` secara otomatis menonaktifkan seluruh akses kredensial akun DVR dan dikecualikan dari jadwal inspeksi rutin lapangan.
* **BR-STR-004 (Import & Standardisasi Template Toko):**
  * Inisialisasi atau pembaruan massal toko wajib mengikuti format template resmi yang disediakan oleh sistem (`GET /api/v1/stores/template`).
  * File import mendukung ekstensi `.csv`, `.xlsx`, dan `.xls` dengan kolom terstandarisasi untuk data toko, serta spesifikasi teknis DVR 1 dan DVR 2 (termasuk kolom `dvr1_serial_number` dan `dvr2_serial_number`).
  * Baris data dengan kode toko yang sudah ada di database akan diperbarui datanya (*upsert*).

---

## 2. Aturan Perangkat DVR (DVR Device Rules)

* **BR-DVR-001 (Identifikasi Jaringan & Port):**
  * Alamat IP DVR harus berupa IPv4 privat yang valid (RFC 1918) dan berada dalam segmen subnet toko yang bersangkutan.
  * Setiap unit DVR wajib mencatat port jaringan: HTTP Web Port (default: 80), RTSP Stream Port (default: 554), dan Server/Media Port (default: 8000).
  * Sistem menyediakan tombol uji koneksi (*Quick Ping Test*) untuk memverifikasi ketersediaan IP DVR secara instan.
* **BR-DVR-002 (Integritas Slot Akun Otomatis):**
  * Setiap kali record DVR baru dibuat (baik via form manual maupun import), sistem **wajib secara otomatis menginisialisasi 5 slot akun departemen terkait** (IC, EDP, SPV, DEV, AUD).
  * Integritas relasi ini dijamin oleh database constraint `UNIQUE KEY (dvr_id, department_id)` dan `UNIQUE KEY (dvr_id, account_slot)`.
* **BR-DVR-003 (Status Operasional):**
  * Nilai status DVR yang didukung: `Online`, `Offline`, `Degraded` (misal saluran kamera bermasalah), `Maintenance`, `Decommissioned`.
* **BR-DVR-004 (Pembaruan Hardware & Serial Number):**
  * Modifikasi data teknis DVR (IP, Port, Serial Number, Kapasitas TB, Hari Retensi) dapat dilakukan sewaktu-waktu oleh teknisi atau Super Admin melalui modal *Edit Detail DVR* tanpa mereset atau menghapus akun departemen yang sudah terkonfigurasi.

---

## 3. Aturan Akun Departemen DVR (Account Allocation & Credential Rules)

* **BR-ACC-001 (Pemetaan Baku 5 Slot Departemen):**
  Setiap 1 unit DVR dialokasikan tepat 5 slot akun departemen:
  1. **Slot 1 - IC (Inventory Control):** Wewenang *Live View & Playback* khusus area gudang, stockroom, dan kasir.
  2. **Slot 2 - EDP / IT Support:** Wewenang *Administrator / Full Access* untuk konfigurasi firmware, IP, port, dan pemeliharaan.
  3. **Slot 3 - SPV / Supervisor Area:** Wewenang *Live View Only* area publik, pintu masuk utama, dan perimeter toko.
  4. **Slot 4 - DEV / Team Development:** Wewenang *Live View* area kasir, antrean, dan sales area.
  5. **Slot 5 - AUD / Internal Audit:** Wewenang *Playback & Export* semua channel untuk investigasi temuan selisih barang atau fraud kasir.
* **BR-ACC-002 (Isolasi Visibilitas Departemen):**
  * Pengguna sistem dengan peran `dept_operator` yang terikat pada departemen tertentu **hanya diizinkan melihat dan membuka password akun miliknya sendiri** (contoh: user operator IC hanya bisa melihat akun slot IC).
  * Akun divisi lain disembunyikan (*masked/hidden*) dari antarmuka pengguna `dept_operator`.
  * Hanya pengguna dengan peran `superadmin` yang memiliki wewenang melihat seluruh 5 akun departemen sekaligus.
* **BR-ACC-003 (Kriptografi Kredensial & Secure Reveal):**
  * Password akun DVR wajib dienkripsi di tingkat aplikasi menggunakan algoritma simetris yang aman (`AES-256-CBC` via Laravel `Crypt::encryptString`).
  * Password teks terbuka (*plaintext*) dilarang keras disimpan di kolom database mana pun maupun pada berkas log aplikasi (`storage/logs`).
  * Fitur *Reveal Password* membuka kata sandi teks terbuka selama maksimal **15 detik**, setelah itu kata sandi otomatis disamarkan kembali.
  * Setiap pembukaan password wajib memicu pencatatan audit log `CREDENTIAL_REVEAL`.

---

## 4. Aturan Akses Jaringan & Deployment (LAN vs WAN Rules)

* **BR-NET-001 (Pembedaan Mode Jaringan):**
  * Sistem mendeteksi sumber request: `LAN` (Subnet Kantor Pusat/Cabang) atau `WAN` (Subnet Toko/VPN eksternal/Public IP).
* **BR-NET-002 (Pembatasan Ekspor WAN):**
  * Fitur *Bulk Export* data master toko dan kredensial dalam format Excel diizinkan langsung melalui jaringan LAN Kantor Pusat.
  * Jika ekspor diakses dari jaringan WAN/Internet, sistem mewajibkan verifikasi kode OTP (*One-Time Password*) sebelum berkas unduhan diterbitkan.
* **BR-NET-003 (Direct RTSP/Stream Policy):**
  * Aplikasi CDAMS bertindak sebagai direktori aset, kredensial, dan diagnostik. Aplikasi tidak melakukan proxy streaming video langsung dari DVR toko ke browser demi menghindari saturasi bandwidth link WAN toko.
* **BR-NET-004 (Reverse Proxy & NAT Port Forwarding):**
  * Sistem mendukung deployment di balik NAT / Port Forwarding (misal: IP Public `202.46.148.102:8888` diteruskan ke IP Server LAN `192.168.25.50:8000`).
  * Konfigurasi aplikasi mempercayai seluruh reverse proxy (`trustProxies(at: '*')`) guna memastikan header `X-Forwarded-For` diproses dengan benar sehingga IP klien asli tercatat pada audit log.

---

## 5. Aturan Pengecekan & Checklist Lapangan (Audit & Check Rules)

* **BR-CHK-001 (Frekuensi & Status Overdue):**
  * Pengecekan fisik dan jaringan berkala dilakukan minimal **1 kali per 30 hari** per DVR oleh teknisi lapangan.
  * Unit DVR yang tidak diperiksa dalam waktu $> 45$ hari secara otomatis ditandai berstatus *Check Overdue*.
* **BR-CHK-002 (Verifikasi Waktu Jam DVR vs NTP Server):**
  * Selisih waktu RTC/jam pada unit DVR dengan waktu server NTP perusahaan diverifikasi saat kunjungan.
  * Jika selisih waktu $> 180$ detik, checklist wajib ditandai `Time Out of Sync` dan sistem menampilkan badge peringatan temuan abnormalitas.
* **BR-CHK-003 (Sinkronisasi Waktu Nyata & Rekapitulasi Checklist):**
  * Setiap submit form checklist lapangan berhasil, sistem **wajib memperbarui kolom `dvrs.last_check_at`** pada unit DVR terkait.
  * Pada halaman detail toko, sistem menampilkan timestamp checklist terakhir yang terformat rapi (`d M Y, H:i WIB`) beserta identitas teknisi pelaksana.
  * Rekapitulasi checklist lapangan dapat dipantau terpusat pada menu `/checks`, dilengkapi filter temuan abnormal (NTP out-of-sync, HDD Error, kamera rusak).

---

## 6. Aturan Manajemen Pengguna & Hak Akses Sistem (User RBAC Rules)

* **BR-USR-001 (Otoritas Modifikasi Pengguna):**
  * Hanya pengguna dengan peran `superadmin` yang memiliki hak akses membuka menu `/users` untuk membuat, mengedit profil, mengubah role, mereset password, atau menghapus pengguna sistem.
* **BR-USR-002 (Role Binding):**
  * Nilai role yang sah: `superadmin`, `technician`, `dept_operator`, `management`.
  * Pengguna dengan role `dept_operator` wajib diasosiasikan dengan salah satu `department_id` yang valid.
  * Pengguna dengan role `superadmin` dan `technician` memiliki cakupan lintas departemen.
* **BR-USR-003 (Proteksi Akun Sendiri):**
  * Super Admin tidak dapat menonaktifkan status aktif (`is_active = false`) atau menghapus akun miliknya sendiri yang sedang digunakan untuk login guna mencegah *lockout* administratif.