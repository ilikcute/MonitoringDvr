# Audit Log Specification

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Kebutuhan Regulasi** | Non-Repudiation, Traceability, Information Security Standard |

---

## 1. Tujuan & Ruang Lingkup
Audit log mencatat setiap aktivitas yang menyangkut keamanan, kredensial akun, perubahan konfigurasi perangkat, dan riwayat login dari jaringan LAN maupun WAN guna mendeteksi potensi penyalahgunaan wewenang.

---

## 2. Struktur Tabel `audit_logs`

```sql
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL COMMENT 'LOGIN, VIEW_PASSWORD, UPDATE_IP, EXPORT_DATA, dll',
    `target_type` VARCHAR(50) NOT NULL COMMENT 'Store, Dvr, DvrAccount',
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

| Kategori | Nama Event (`action`) | Trigger Kondisi | Payload Data yang Disimpan |
|---|---|---|---|
| **Autentikasi** | `AUTH_LOGIN_SUCCESS` | Pengguna berhasil login | IP address, Network Type (LAN/WAN), User Agent |
| **Autentikasi** | `AUTH_LOGIN_FAILED` | Gagal password 3x | Username percobaan, IP address, Network Type |
| **Akses Kredensial** | `CREDENTIAL_REVEAL` | User menekan tombol intip password akun DVR | `dvr_account_id`, `department_code`, ID DVR, Kode Toko |
| **Modifikasi Aset** | `DVR_IP_CHANGED` | IP DVR atau Port diganti | IP lama vs IP baru, user pelaksana |
| **Modifikasi Aset** | `ACCOUNT_PASSWORD_CHANGED` | Password akun DVR diubah | Status berhasil diperbarui (tanpa mencatat password teks terbuka) |
| **Ekspor Data** | `MASS_DATA_EXPORT` | User mengunduh file spreadsheet daftar toko/DVR | Format file, jumlah record, IP, tipe jaringan |

---

## 4. Kebijakan Retensi & Integritas Data Log

1. **Immutability (Anti-Tampering):** Record pada tabel `audit_logs` bersifat *Append-Only*. Tidak ada perintah `UPDATE` atau `DELETE` yang diizinkan pada level aplikasi.
2. **Retensi:** Data log disimpan minimum selama **365 hari** (1 tahun). Log yang lebih lama dari 1 tahun diarsipkan otomatis ke cold storage terkompresi.