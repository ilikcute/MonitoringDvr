# Database Design Specification (MySQL 8.x)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Engine** | MySQL 8.0+ (InnoDB) / MariaDB |
| **Collation** | `utf8mb4_unicode_ci` |
| **Versi Desain** | 1.2.0 |

---

## 1. Entity Relationship Diagram (ERD)

```
[ departments ] 1 ────────────< 0..N [ users ]
       │                                │
       │ (1:5 standard)                 │ (Checked By)
       ▼                                ▼
[ dvr_accounts ] >──────── 1 [ dvrs ] 1 ────< 0..N [ dvr_checks ]
                             │
                             │ (1:2 default, or N via allow_extra_dvr)
                             ▼
                         [ stores ]
                             │
                             ▼ (Actions Audited)
                       [ audit_logs ] <── [ users ]
```

---

## 2. Definisi Struktur Tabel Lengkap

### 2.1 Tabel `departments`
Menyimpan daftar master departemen internal perusahaan.
```sql
CREATE TABLE `departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Kode baku: IC, EDP, SPV, DEV, AUD',
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Tabel `users`
Menyimpan akun pengguna sistem aplikasi beserta peran (*role*) dan asosiasi departemen.
```sql
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'technician', 'dept_operator', 'management') NOT NULL DEFAULT 'dept_operator',
    `department_id` BIGINT UNSIGNED NULL,
    `phone` VARCHAR(30) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_users_dept_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 Tabel `stores`
Menyimpan master direktori ~666 toko ritel beserta detail kontak dan status operasional.
```sql
CREATE TABLE `stores` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: T001 s.d T666',
    `store_name` VARCHAR(150) NOT NULL,
    `region` VARCHAR(50) NOT NULL COMMENT 'Jabodetabek, Jawa Barat, Jawa Tengah, dll',
    `address` TEXT NULL,
    `ip_subnet` VARCHAR(45) NULL COMMENT 'Contoh: 10.120.45.0/24',
    `contact_person` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `status` ENUM('Active', 'Renovation', 'Closed') NOT NULL DEFAULT 'Active',
    `allow_extra_dvr` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Override batas kapasitas default 2 unit',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_store_code` (`store_code`),
    INDEX `idx_region` (`region`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.4 Tabel `dvrs`
Menyimpan identitas fisik perangkat DVR, Serial Number, konfigurasi port, kapasitas rekaman, serta waktu checklist terakhir.
```sql
CREATE TABLE `dvrs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `dvr_index` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 untuk DVR 1, 2 untuk DVR 2',
    `label` VARCHAR(100) NOT NULL COMMENT 'Contoh: DVR 1 - Area Toko & Kasir',
    `brand` VARCHAR(60) NOT NULL DEFAULT 'Hikvision',
    `model_series` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NULL COMMENT 'Serial number fisik unit DVR',
    `ip_address` VARCHAR(45) NOT NULL DEFAULT '192.168.25.200',
    `http_port` INT UNSIGNED NOT NULL DEFAULT 80,
    `rtsp_port` INT UNSIGNED NOT NULL DEFAULT 554,
    `server_port` INT UNSIGNED NOT NULL DEFAULT 8000 COMMENT 'Client/Media port SDK',
    `total_channels` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT '4, 8, 16, 32',
    `storage_capacity_tb` DECIMAL(4,1) NULL,
    `retention_days` SMALLINT UNSIGNED NULL COMMENT 'Estimasi lama hari penyimpanan rekaman',
    `firmware_version` VARCHAR(50) NULL,
    `status` ENUM('Online', 'Offline', 'Degraded', 'Maintenance', 'Decommissioned') NOT NULL DEFAULT 'Offline',
    `last_seen_at` TIMESTAMP NULL,
    `last_check_at` TIMESTAMP NULL COMMENT 'Waktu checklist lapangan terakhir',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_store_dvr_index` (`store_id`, `dvr_index`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_status` (`status`),
    INDEX `idx_serial_number` (`serial_number`),
    CONSTRAINT `fk_dvrs_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.5 Tabel `dvr_accounts`
Menyimpan kredensial terenkripsi 5 akun departemen per unit DVR.
```sql
CREATE TABLE `dvr_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dvr_id` BIGINT UNSIGNED NOT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `account_slot` TINYINT UNSIGNED NOT NULL COMMENT 'Slot 1 sd 5 (IC, EDP, SPV, DEV, AUD)',
    `username` VARCHAR(50) NOT NULL,
    `encrypted_password` TEXT NOT NULL COMMENT 'Payload terenkripsi AES-256 via Laravel Crypt::encryptString',
    `permission_profile` VARCHAR(100) NOT NULL COMMENT 'Contoh: Live View Kasir, Administrator, Playback All',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dvr_department` (`dvr_id`, `department_id`),
    UNIQUE KEY `uk_dvr_slot` (`dvr_id`, `account_slot`),
    CONSTRAINT `fk_accounts_dvr_id` FOREIGN KEY (`dvr_id`) REFERENCES `dvrs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_accounts_dept_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.6 Tabel `dvr_checks`
Menyimpan riwayat pemeriksaan kondisi perangkat CCTV dari teknisi di lapangan.
```sql
CREATE TABLE `dvr_checks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dvr_id` BIGINT UNSIGNED NOT NULL,
    `checked_by_user_id` BIGINT UNSIGNED NOT NULL,
    `check_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_ping_online` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_time_synced` BOOLEAN NOT NULL DEFAULT TRUE,
    `time_difference_seconds` INT NOT NULL DEFAULT 0 COMMENT 'Selisih waktu RTC DVR dengan server NTP (detik)',
    `hdd_status` ENUM('Normal', 'Error', 'Unformatted', 'Full') NOT NULL DEFAULT 'Normal',
    `record_retention_days` SMALLINT UNSIGNED NULL COMMENT 'Estimasi hari rekaman tersedia',
    `camera_working_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `camera_broken_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `network_type` ENUM('LAN', 'WAN') NOT NULL DEFAULT 'LAN',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_dvr_check_time` (`dvr_id`, `check_timestamp`),
    INDEX `idx_checker` (`checked_by_user_id`),
    CONSTRAINT `fk_checks_dvr_id` FOREIGN KEY (`dvr_id`) REFERENCES `dvrs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checks_user_id` FOREIGN KEY (`checked_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.7 Tabel `audit_logs`
Menyimpan jejak audit sistem yang bersifat *append-only* (tanpa fitur update/delete).
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

## 3. Tabel Pendukung Sistem (Laravel Infrastructure)

1. **`personal_access_tokens`**: Penyimpanan Bearer Token otentikasi Sanctum bagi pengguna aplikasi web.
2. **`password_reset_tokens`**: Token verifikasi reset kata sandi.
3. **`sessions`**: Manajemen session web server.
4. **`cache` & `cache_locks`**: Cache performa query database.
5. **`jobs` & `failed_jobs`**: Antrean proses background asinkron (ekspor/impor massal).