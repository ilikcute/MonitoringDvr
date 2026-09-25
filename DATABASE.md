# Database Design Specification (MySQL 8.x)

| Metadata | Nilai |
|---|---|
| **Sistem** | CCTV DVR Asset & Access Management System (CDAMS) |
| **Engine** | MySQL 8.0+ (InnoDB) |
| **Collation** | `utf8mb4_unicode_ci` |

---

## 1. Entity Relationship Diagram (ERD - Chen/Relational Notation)

```
[ stores ] 1 ──────── 1..2 (or N) [ dvrs ]
                              │
            ┌─────────────────┴─────────────────┐
            │ 1                                 │ 1
            ▼ 5 (exact)                         ▼ N
    [ dvr_accounts ]                    [ dvr_checks ]
            ▲                                   ▲
            │                                   │
      (Read Auth)                         (Checked By)
            │                                   │
[ departments ] 1 ──── N [ users ] 1 ───────────┘
                           │
                           ▼ 1..N
                   [ audit_logs ]
```

---

## 2. Definisi Struktur Tabel Lengkap

### 2.1 Tabel `departments`
Menyimpan daftar departemen internal resmi.
```sql
CREATE TABLE `departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'IC, EDP, SEC, OPS, AUDIT',
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Tabel `stores`
Menyimpan direktori ~666 toko ritel.
```sql
CREATE TABLE `stores` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: T001 s.d T666',
    `store_name` VARCHAR(150) NOT NULL,
    `region` VARCHAR(50) NOT NULL COMMENT 'Region 1, Region 2, Jabodetabek, dll',
    `address` TEXT NULL,
    `ip_subnet` VARCHAR(45) NULL COMMENT 'Contoh: 10.120.45.0/24',
    `status` ENUM('Active', 'Renovation', 'Closed') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_store_code` (`store_code`),
    INDEX `idx_region` (`region`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 Tabel `dvrs`
Menyimpan perangkat fisik DVR (1 atau 2 unit per toko).
```sql
CREATE TABLE `dvrs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `dvr_index` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 untuk DVR 1, 2 untuk DVR 2',
    `label` VARCHAR(100) NOT NULL COMMENT 'Contoh: DVR 1 - Kasir & Toko, DVR 2 - Gudang',
    `brand` VARCHAR(60) NOT NULL DEFAULT 'Hikvision',
    `model_series` VARCHAR(100) NULL,
    `serial_number` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `http_port` INT UNSIGNED NOT NULL DEFAULT 80,
    `rtsp_port` INT UNSIGNED NOT NULL DEFAULT 554,
    `server_port` INT UNSIGNED NOT NULL DEFAULT 8000 COMMENT 'Client/Media port SDK',
    `total_channels` TINYINT UNSIGNED NOT NULL DEFAULT 8 COMMENT '4, 8, 16, 32',
    `storage_capacity_tb` DECIMAL(4,1) NULL,
    `firmware_version` VARCHAR(50) NULL,
    `status` ENUM('Online', 'Offline', 'Degraded', 'Maintenance', 'Decommissioned') NOT NULL DEFAULT 'Offline',
    `last_seen_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_store_dvr_index` (`store_id`, `dvr_index`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_dvrs_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.4 Tabel `dvr_accounts`
Menyimpan data 5 akun per DVR.
```sql
CREATE TABLE `dvr_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dvr_id` BIGINT UNSIGNED NOT NULL,
    `department_id` BIGINT UNSIGNED NOT NULL,
    `account_slot` TINYINT UNSIGNED NOT NULL COMMENT 'Slot 1 sd 5',
    `username` VARCHAR(50) NOT NULL,
    `encrypted_password` TEXT NOT NULL COMMENT 'Payload AES-256 via Laravel Crypt',
    `permission_profile` VARCHAR(100) NOT NULL COMMENT 'Contoh: Live-Only, Playback-Only, Admin',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dvr_department` (`dvr_id`, `department_id`),
    UNIQUE KEY `uk_dvr_slot` (`dvr_id`, `account_slot`),
    CONSTRAINT `fk_accounts_dvr_id` FOREIGN KEY (`dvr_id`) REFERENCES `dvrs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_accounts_dept_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.5 Tabel `dvr_checks`
Menyimpan riwayat pemeriksaan kondisi perangkat dari lapangan.
```sql
CREATE TABLE `dvr_checks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dvr_id` BIGINT UNSIGNED NOT NULL,
    `checked_by_user_id` BIGINT UNSIGNED NOT NULL,
    `check_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_ping_online` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_time_synced` BOOLEAN NOT NULL DEFAULT TRUE,
    `time_difference_seconds` INT NOT NULL DEFAULT 0,
    `hdd_status` ENUM('Normal', 'Error', 'Unformatted', 'Full') NOT NULL DEFAULT 'Normal',
    `record_retention_days` SMALLINT UNSIGNED NULL COMMENT 'Estimasi hari rekaman tersedia',
    `camera_working_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `camera_broken_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `network_type` ENUM('LAN', 'WAN') NOT NULL DEFAULT 'LAN',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_dvr_check_time` (`dvr_id`, `check_timestamp`),
    INDEX `idx_checker` (`checked_by_user_id`),
    CONSTRAINT `fk_checks_dvr_id` FOREIGN KEY (`dvr_id`) REFERENCES `dvrs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checks_user_id` FOREIGN KEY (`checked_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```