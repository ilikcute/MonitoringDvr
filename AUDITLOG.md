# Audit Log Specification

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Kebutuhan Regulasi** | Non-Repudiation, Traceability, Information Security Standard |
| **Versi Dokumen** | 1.2.0 |

---

## 1. Tujuan & Ruang Lingkup

Audit log berfungsi mencatat seluruh aktivitas esensial sistem secara tidak dapat diubah (*append-only* dan *tamper-proof*), mencakup autentikasi sesi, pengungkapan kata sandi sensitif (*credential reveal*), perubahan spesifikasi perangkat keras/jaringan DVR, mutasi akun pengguna, serta ekspor dan impor data massal dari jaringan internal (`LAN`) maupun eksternal (`WAN`).

---

## 2. Struktur Tabel `audit_logs`

Struktur tabel persis sesuai migrasi database MySQL:
```sql
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'AUTH_LOGIN_SUCCESS, CREDENTIAL_REVEAL, DVR_IP_CHANGED, USER_CREATED, dll',
    `target_type` VARCHAR(50) NULL COMMENT 'Store, Dvr, DvrAccount, User',
    `target_id` BIGINT UNSIGNED NULL,
    `department_code` VARCHAR(20) NULL COMMENT 'Departemen akun jika terkait intip password',
    `network_type` ENUM('LAN', 'WAN') NOT NULL DEFAULT 'LAN',
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_action_time` (`action`, `created_at`),
    INDEX `idx_user_action` (`user_id`, `created_at`),
    INDEX `idx_target` (`target_type`, `target_id`),
    CONSTRAINT `fk_audit_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Matriks Peristiwa yang Wajib Dicatat (Logged Events)

| Kategori | Nama Event (`action`) | Trigger Kondisi | Target Type | Payload Data (`old_values` / `new_values`) |
|---|---|---|---|---|
| **Autentikasi** | `AUTH_LOGIN_SUCCESS` | Pengguna berhasil login | `User` | ID pengguna, email, role, IP, network type |
| **Autentikasi** | `AUTH_LOGIN_FAILED` | Percobaan login gagal | - | Email percobaan, IP address, network type |
| **Autentikasi** | `AUTH_LOGOUT` | Pengguna logout dari sistem | `User` | ID pengguna, waktu sesi berakhir |
| **Kredensial** | `CREDENTIAL_REVEAL` | Pengguna menekan tombol intip password akun DVR | `DvrAccount` | Kode departemen (`department_code`), ID akun, ID DVR |
| **Kredensial** | `ACCOUNT_PASSWORD_CHANGED` | Kata sandi akun departemen diubah | `DvrAccount` | Status perubahan password sukses (tanpa plaintext) |
| **Kredensial** | `ACCOUNT_PROFILE_CHANGED` | Username atau izin akses akun diperbarui | `DvrAccount` | Profil izin lama vs baru, username lama vs baru |
| **Checklist** | `DVR_CHECK_SUBMITTED` | Teknisi mengirim laporan checklist lapangan | `Dvr` | Hasil ping, selisih jam NTP, status HDD, jumlah kamera |
| **Perangkat DVR** | `DVR_CREATED` | Unit DVR baru ditambahkan ke toko | `Dvr` | Label, IP address, port, serial number, storage |
| **Perangkat DVR** | `DVR_UPDATED` | Spesifikasi DVR (SN, storage, retensi) diubah | `Dvr` | Parameter teknis lama vs parameter teknis baru |
| **Perangkat DVR** | `DVR_IP_CHANGED` | Alamat IP atau port DVR diganti | `Dvr` | IP lama vs IP baru, port lama vs port baru |
| **Perangkat DVR** | `DVR_DELETED` | Unit DVR dihapus dari toko | `Dvr` | Data lengkap unit DVR sebelum dihapus |
| **Master Toko** | `STORE_CREATED` | Gerai/toko baru didaftarkan | `Store` | Kode toko, nama toko, wilayah, IP subnet |
| **Master Toko** | `STORE_UPDATED` | Informasi gerai/toko dimodifikasi | `Store` | Field lama vs field baru |
| **Master Toko** | `STORE_DELETED` | Toko dihapus dari sistem | `Store` | Data toko sebelum dihapus |
| **Ekspor & Impor** | `EXPORT_OTP_REQUESTED` | Permintaan kode OTP ekspor via WAN | `Store` | Email peminta, IP address, waktu permintaan |
| **Ekspor & Impor** | `MASS_DATA_EXPORT` | Unduhan spreadsheet toko & kredensial | `Store` | Jumlah baris data diekspor, mode jaringan, format berkas |
| **Ekspor & Impor** | `MASS_DATA_IMPORT` | Berkas spreadsheet toko & DVR diimpor | `Store` | Nama file, jumlah baris berhasil diproses |
| **Manajemen User**| `USER_CREATED` | Superadmin mendaftarkan pengguna baru | `User` | Nama, email, role, department_id, status aktif |
| **Manajemen User**| `USER_UPDATED` | Superadmin mengubah profil/role/status user | `User` | Nilai data user sebelum vs sesudah perubahan |
| **Manajemen User**| `USER_DELETED` | Superadmin menghapus akun pengguna | `User` | Data profil pengguna sebelum dihapus |

---

## 4. Deteksi Jaringan & Resolusi IP (LAN vs WAN)

1. **Klasifikasi Jaringan (`network_type`):**
   - Nilai `LAN`: Klien berasal dari subnet privat kantor pusat (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16` Head Office).
   - Nilai `WAN`: Klien mengakses melalui jaringan luar/internet, IP public, atau port forwarding.
2. **Dukungan NAT & Reverse Proxy:**
   - Aplikasi dikonfigurasi dengan `trustProxies(at: '*')` pada `bootstrap/app.php`.
   - Ketika server diakses melalui port forwarding router (misal: dari IP Public `202.46.148.102:8888` ke IP Server LAN `192.168.25.50:8000`), header `X-Forwarded-For` dipetakan secara akurat sehingga IP riil klien yang mengakses selalu tercatat pada kolom `ip_address` tabel `audit_logs`.

---

## 5. Kebijakan Retensi & Integritas Data Log

1. **Immutability (Anti-Tampering):**
   - Catatan pada tabel `audit_logs` berprinsip *Write-Once, Read-Many (WORM)*.
   - Tidak ada antarmuka pengguna maupun endpoint API yang menyediakan fungsi `UPDATE` atau `DELETE` untuk record audit log.
2. **Retensi & Kepatuhan:**
   - Seluruh catatan log disimpan aktif di database utama sekurang-kurangnya **365 hari** (1 tahun).
   - Log yang melampaui masa 1 tahun dapat diarsipkan secara berkala ke penyimpanan terkompresi terisolasi (*cold storage*).