# API Specification (RESTful)

| Metadata | Nilai |
|---|---|
| **Base URL** | `/api/v1` |
| **Autentikasi** | Laravel Sanctum Bearer Token |
| **Header Standar** | `Accept: application/json`, `Content-Type: application/json` |
| **Versi Dokumen** | 1.2.0 |

---

## 1. Modul Autentikasi & Akun Sesi

### 1.1 Login Pengguna
* **Endpoint:** `POST /api/v1/auth/login`
* **Akses:** Publik
* **Request Body:**
```json
{
  "email": "admin@indomaret.test",
  "password": "password"
}
```
* **Response `200 OK`:**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|abcde12345...",
    "user": {
      "id": 1,
      "name": "Super Admin EDP",
      "email": "admin@indomaret.test",
      "role": "superadmin",
      "department": null
    }
  }
}
```

### 1.2 Get Data Profil Sesi Saat Ini
* **Endpoint:** `GET /api/v1/auth/me`
* **Akses:** Terautentikasi (Bearer Token)
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Super Admin EDP",
    "email": "admin@indomaret.test",
    "role": "superadmin",
    "department": null
  }
}
```

### 1.3 Logout Pengguna
* **Endpoint:** `POST /api/v1/auth/logout`
* **Akses:** Terautentikasi (Bearer Token)
* **Response `200 OK`:**
```json
{
  "success": true,
  "message": "Logout berhasil."
}
```

---

## 2. Modul Dashboard & Metrik

### 2.1 Ringkasan Metrik Dashboard
* **Endpoint:** `GET /api/v1/dashboard`
* **Akses:** Terautentikasi
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "total_stores": 666,
    "total_dvrs": 1332,
    "online_dvrs": 1280,
    "offline_dvrs": 52,
    "pending_checks": 14,
    "network_mode": "LAN",
    "recent_activities": [
      {
        "id": 901,
        "action": "CREDENTIAL_REVEAL",
        "created_at": "2026-09-25T07:15:00.000000Z",
        "user": { "name": "Super Admin EDP", "role": "superadmin" }
      }
    ]
  }
}
```

---

## 3. Modul Manajemen Pengguna & RBAC

### 3.1 Daftar Pengguna Sistem
* **Endpoint:** `GET /api/v1/users`
* **Akses:** Hanya `superadmin`
* **Query Params:** `search`, `role`, `department_id`, `per_page`, `page`

### 3.2 Tambah Pengguna Baru
* **Endpoint:** `POST /api/v1/users`
* **Akses:** Hanya `superadmin`
* **Request Body:**
```json
{
  "name": "Budi Santoso",
  "email": "budi.teknisi@indomaret.test",
  "password": "SecretPassword#123",
  "role": "technician",
  "department_id": null,
  "phone": "081234567890",
  "is_active": true
}
```

### 3.3 Detail, Update, & Hapus Pengguna
* **Detail:** `GET /api/v1/users/{id}`
* **Update:** `PUT /api/v1/users/{id}`
* **Hapus:** `DELETE /api/v1/users/{id}`

---

## 4. Modul Toko (Stores)

### 4.1 List Toko (Pagination, Filter, & Search)
* **Endpoint:** `GET /api/v1/stores`
* **Query Params:**
  * `search` (string) - Kode toko atau nama toko
  * `region` (string) - Filter wilayah
  * `status` (string) - `Active`, `Renovation`, `Closed`
  * `per_page` (int, default: 15), `page` (int)

### 4.2 Tambah Toko Baru
* **Endpoint:** `POST /api/v1/stores`
* **Request Body:**
```json
{
  "store_code": "T667",
  "store_name": "Toko Simpang Lima",
  "region": "Jawa Tengah",
  "address": "Jl. Pahlawan No. 45, Semarang",
  "ip_subnet": "10.120.55.0/24",
  "contact_person": "Agus Salim",
  "phone": "081399887766",
  "status": "Active",
  "allow_extra_dvr": false
}
```

### 4.3 Detail Toko Lengkap
* **Endpoint:** `GET /api/v1/stores/{id}`
* **Response:** Mengembalikan detail toko, daftar unit DVR lengkap beserta Serial Number, status 5 akun departemen, dan riwayat checklist terakhir.

### 4.4 Update & Hapus Toko
* **Update:** `PUT /api/v1/stores/{id}`
* **Hapus:** `DELETE /api/v1/stores/{id}`

### 4.5 Unduh Template Import
* **Endpoint:** `GET /api/v1/stores/template`
* **Akses:** Terautentikasi
* **Output:** File spreadsheet Excel/CSV template resmi yang memuat kolom master toko serta data `dvr1_serial_number` dan `dvr2_serial_number`.

### 4.6 Impor Data Toko Massal
* **Endpoint:** `POST /api/v1/stores/import`
* **Request:** `multipart/form-data` dengan parameter `file` (format `.csv`, `.xlsx`, max 10MB).

### 4.7 Request OTP & Ekspor Data Toko
* **Request OTP (Mode WAN):** `GET /api/v1/stores/export-otp`
* **Ekspor Data:** `GET /api/v1/stores/export` (Menerima parameter filter dan query param `otp` jika diakses dari jaringan WAN).

---

## 5. Modul Perangkat DVR & Kredensial

### 5.1 Quick Ping Test
* **Endpoint:** `POST /api/v1/dvrs/ping-test` atau `POST /api/v1/dvrs/{id}/ping-test`
* **Request Body:** `{ "ip_address": "10.10.1.200" }`
* **Response `200 OK`:** `{ "success": true, "online": true, "latency_ms": 18 }`

### 5.2 Tambah DVR pada Toko
* **Endpoint:** `POST /api/v1/stores/{store_id}/dvrs`
* **Request Body:**
```json
{
  "dvr_index": 1,
  "label": "DVR 1 - Area Toko & Kasir",
  "brand": "Hikvision",
  "model_series": "DS-7208HQHI-K1",
  "serial_number": "DS7208HQHIK123456789",
  "ip_address": "10.10.1.200",
  "http_port": 80,
  "rtsp_port": 554,
  "server_port": 8000,
  "total_channels": 8,
  "storage_capacity_tb": 2.0,
  "retention_days": 30
}
```

### 5.3 Detail DVR
* **Endpoint:** `GET /api/v1/dvrs/{id}`

### 5.4 Update Spesifikasi DVR & Serial Number
* **Endpoint:** `PUT /api/v1/dvrs/{id}`
* **Request Body:**
```json
{
  "label": "DVR 1 - Area Kasir & Sales Utama",
  "brand": "Hikvision",
  "model_series": "DS-7208HQHI-K1",
  "serial_number": "DS7208HQHI-REV2-9988",
  "ip_address": "10.10.1.200",
  "http_port": 80,
  "rtsp_port": 554,
  "server_port": 8000,
  "total_channels": 8,
  "storage_capacity_tb": 4.0,
  "retention_days": 45,
  "status": "Online"
}
```

### 5.5 Hapus DVR
* **Endpoint:** `DELETE /api/v1/dvrs/{id}`

### 5.6 Intip Password Akun Departemen (Secure Reveal)
* **Endpoint:** `POST /api/v1/dvrs/{dvr_id}/accounts/{account_id}/reveal-password`
* **Otorisasi:** User dengan role `dept_operator` hanya berhak melihat akun departemen miliknya sendiri. Super Admin berhak melihat semua akun.
* **Audit Trail:** Mencatat log aksi `CREDENTIAL_REVEAL`.
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "account_id": 501,
    "username": "ic_viewer",
    "decrypted_password": "PlaintextPasswordToko#123",
    "expires_in_seconds": 15
  }
}
```

### 5.7 Update Kredensial Akun Departemen
* **Endpoint:** `PUT /api/v1/dvrs/{dvr_id}/accounts/{account_id}`
* **Request Body:**
```json
{
  "username": "ic_viewer_new",
  "password": "NewSecurePassword#2026",
  "permission_profile": "Live View Channel 1-4 & Playback",
  "is_active": true
}
```

---

## 6. Modul Checklist Lapangan (DVR Checks)

### 6.1 Rekapitulasi Seluruh Checklist Lapangan
* **Endpoint:** `GET /api/v1/checks`
* **Query Params:** `search`, `abnormal_only` (`true`/`false`), `per_page`, `page`
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": [
    {
      "id": 105,
      "dvr_id": 1,
      "store_code": "T001",
      "store_name": "Toko Grand Central",
      "dvr_label": "DVR 1 - Area Toko & Kasir",
      "checked_by": "Syahdat",
      "check_timestamp": "2026-09-25 10:30:00",
      "formatted_date": "25 Sep 2026, 10:30 WIB",
      "is_ping_online": true,
      "is_time_synced": false,
      "time_difference_seconds": 240,
      "hdd_status": "Normal",
      "camera_working_count": 8,
      "camera_broken_count": 0,
      "has_abnormalities": true,
      "notes": "Jam DVR terlambat 4 menit."
    }
  ],
  "meta": { "current_page": 1, "total_records": 120 }
}
```

### 6.2 Submit Hasil Checklist Lapangan Baru
* **Endpoint:** `POST /api/v1/dvrs/{dvr_id}/checks`
* **Request Body:**
```json
{
  "is_ping_online": true,
  "is_time_synced": true,
  "time_difference_seconds": 15,
  "hdd_status": "Normal",
  "record_retention_days": 30,
  "camera_working_count": 8,
  "camera_broken_count": 0,
  "network_type": "WAN",
  "notes": "Pemeriksaan rutin berkala selesai, kondisi prima."
}
```
* **Efek Samping:** Secara otomatis mengupdate `dvrs.last_check_at` dengan timestamp saat ini dan mencatat audit log `DVR_CHECK_SUBMITTED`.

### 6.3 Daftar DVR Overdue Pemeriksaan
* **Endpoint:** `GET /api/v1/checks/overdue`

---

## 7. Modul Audit Logs

### 7.1 Riwayat Audit Log Sistem
* **Endpoint:** `GET /api/v1/audit-logs`
* **Akses:** Khusus `superadmin`
* **Query Params:** `action`, `search`, `network_type`, `per_page`, `page`
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": [
    {
      "id": 340,
      "user_name": "Super Admin EDP",
      "action": "CREDENTIAL_REVEAL",
      "target_type": "DvrAccount",
      "target_id": 501,
      "department_code": "IC",
      "network_type": "LAN",
      "ip_address": "192.168.25.105",
      "created_at": "2026-09-25T07:15:00.000000Z"
    }
  ]
}
```