# Business Rules Specification (BRD-RULE)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Versi Dokumen** | 1.0.0 |
| **Status** | Approved for Implementation |

---

## 1. Aturan Entitas Toko (Store Rules)

* **BR-STR-001 (Unique Store Code):** Kode toko (`kdtk`) bersifat unik secara global dan tidak boleh diubah setelah dibuat tanpa otorisasi Super Admin.
* **BR-STR-002 (Kapasitas DVR):**
  * Setiap toko wajib memiliki minimal **1 unit DVR** aktif saat status toko `Active`.
  * Batas default kapasitas DVR per toko adalah **maksimal 2 unit DVR** (DVR 1 dan DVR 2).
  * Penambahan unit ke-3 hanya diizinkan melalui *override flag* khusus (`allow_extra_dvr = true`) dengan persetujuan EDP Manager.
  * Setiap DVR memiliki Identitas yang melekat di DVR tersebut seperti Serial Number (SN) yang bersifat unique, Merk DVR, Kapastias Hard Disck dan Lama Penyimpanan Video rekaman
* **BR-STR-003 (Lifecycle Status Toko):**
  * Status toko: `Active`, `Renovation`, `Closed`.
  * Toko dengan status `Closed` menonaktifkan seluruh kredensial akses DVR secara otomatis dan tidak muncul di jadwal checklist berkala.

---

## 2. Aturan Perangkat DVR (DVR Device Rules)

* **BR-DVR-001 (Identifikasi Jaringan):**
  * Kombinasi `ip_address`, `http_port` , rtsp_port harus unik di dalam subnet toko yang sama.
  * Format alamat IP harus valid IPv4 privat (RFC 1918).
* **BR-DVR-002 (Integritas Slot Akun Otomatis):**
  * Setiap kali record DVR baru dibuat, sistem **wajib secara otomatis membuat 5 slot akun departemen terkait** (IC, EDP, SPV, DEV, Audit) dalam status *pending initialization*.
  * DVR tidak dapat dinyatakan `Ready / Operational` jika kelima akun tersebut belum memiliki kredensial valid.
* **BR-DVR-003 (Status Operasional):**
  * Nilai status DVR: `Online`, `Offline`, `Degraded` (misal 1 channel mati), `Maintenance`, `Decommissioned`.

---

## 3. Aturan Akun Departemen DVR (Account Allocation & Credential Rules)

* **BR-ACC-001 (Pemetaan 5 Slot Departemen):**
  Setiap DVR dialokasikan tepat 5 slot pengguna dengan hak akses DVR yang terstandarisasi:
  1. **Slot 1 - IC (Inventory Control):** Wewenang *Live View & Playback* khusus channel area gudang/stockroom dan kasir.
  2. **Slot 2 - EDP / IT Support:** Wewenang *Administrator / Full Access* untuk konfigurasi firmware, IP, dan pemeliharaan.
  3. **Slot 3 - SPV / Supervisor Area:** Wewenang *Live View Only* area publik, pintu masuk, dan perimeter.
  4. **Slot 4 - Developemt / Team Development:** Wewenang *Live View* area kasir dan *sales area*.
  5. **Slot 5 - Internal Audit / Loss Prevention:** Wewenang *Playback & Export* semua channel untuk pembuktian temuan/fraud.
* **BR-ACC-002 (Isolasi Visibilitas Departemen):**
  * User aplikasi dengan peran departemen tertentu (contoh: user departemen IC) **hanya berhak melihat dan mendekripsi password akun IC** pada DVR terkait.
  * User IC dilarang keras melihat kredensial milik EDP, Security, Ops, atau Audit.
  * Hanya Super Admin / EDP Core yang memiliki hak *view/reset* ke semua akun departemen.
* **BR-ACC-003 (Kriptografi Kredensial):**
  * Password akun DVR wajib dienkripsi di tingkat aplikasi menggunakan algoritma simetris yang aman (`AES-256-CBC` via Laravel `Crypt::encryptString`).
  * Password teks terbuka (*plaintext*) tidak boleh disimpan di kolom database mana pun maupun di log aplikasi (`storage/logs`).

---

## 4. Aturan Akses Jaringan (LAN vs WAN Rules)

* **BR-NET-001 (Pembedaan Mode Jaringan):**
  * Sistem mendeteksi sumber request: `LAN` (Subnet Kantor Pusat/Cabang) atau `WAN` (Subnet Toko/VPN eksternal).
* **BR-NET-002 (Pembatasan Ekspor WAN):**
  * Fitur *Bulk Export* seluruh 666 data toko dan kredensial akun dalam format Excel/CSV **hanya dapat diakses melalui jaringan LAN Kantor Pusat** atau dengan *One-Time Password (OTP)* jika melalui WAN.
* **BR-NET-003 (Direct RTSP/Stream Access):**
  * Web app ini bertindak sebagai direktori aset, data akun, dan status diagnostik. Web app **tidak melakukan proxy streaming video langsung** dari 666 toko demi menghindari saturasi bandwidth link WAN toko.

---

## 5. Aturan Pengecekan & Checklist Lapangan (Audit & Check Rules)

* **BR-CHK-001 (Frekuensi Checklist):**
  * Pengecekan rutin fisik/jaringan dilakukan minimal **1 kali per 30 hari** per DVR.
  * DVR yang tidak dicek > 45 hari otomatis berstatus *Check Overdue*.
* **BR-CHK-002 (Verifikasi Waktu Jam DVR):**
  * Jika selisih waktu RTC/jam pada DVR dengan waktu server NTP perusahaan $> 180$ detik, checklist wajib ditandai `Time Out of Sync` dan dibuatkan tiket kendala EDP.