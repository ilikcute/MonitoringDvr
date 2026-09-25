# API Specification (RESTful)

| Metadata | Nilai |
|---|---|
| **Base URL** | `/api/v1` |
| **Autentikasi** | Laravel Sanctum Bearer Token |
| **Header Standar** | `Accept: application/json`, `Content-Type: application/json` |

---

## 1. Modul Toko (Stores)

### 1.1 List Toko (Pagination & Filter)
* **Endpoint:** `GET /api/v1/stores`
* **Query Params:**
  * `search` (string, optional) - Kode toko atau nama toko
  * `region` (string, optional) - Filter region/wilayah
  * `status` (string, optional) - `Active`, `Renovation`, `Closed`
  * `page` (int, default: 1), `per_page` (int, default: 15)
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "store_code": "T001",
      "store_name": "Toko Grand Central",
      "region": "Jabodetabek",
      "ip_subnet": "10.10.1.0/24",
      "status": "Active",
      "dvrs_count": 2,
      "dvrs": [
        {
          "id": 101,
          "dvr_index": 1,
          "label": "DVR 1 - Area Toko & Kasir",
          "ip_address": "10.10.1.200",
          "status": "Online"
        },
        {
          "id": 102,
          "dvr_index": 2,
          "label": "DVR 2 - Area Gudang",
          "ip_address": "10.10.1.201",
          "status": "Online"
        }
      ]
    }
  ],
  "meta": { "current_page": 1, "total_records": 666, "last_page": 45 }
}
```

---

## 2. Modul DVR & Kredensial

### 2.1 Detail DVR Beserta Akun Departemen
* **Endpoint:** `GET /api/v1/dvrs/{id}`
* **Authorization:** Role-checked. User biasa hanya menerima detail akun divisinya. Super Admin menerima 5 akun.
* **Response `200 OK`:**
```json
{
  "success": true,
  "data": {
    "id": 101,
    "store_code": "T001",
    "dvr_index": 1,
    "brand": "Hikvision",
    "ip_address": "10.10.1.200",
    "http_port": 80,
    "rtsp_port": 554,
    "accounts": [
      {
        "id": 501,
        "department_code": "IC",
        "username": "ic_viewer",
        "has_password": true,
        "permission_profile": "Live View Channel 1-4"
      }
    ]
  }
}
```

### 2.2 Reveal Password Akun (Secure Endpoint)
* **Endpoint:** `POST /api/v1/dvrs/{dvr_id}/accounts/{account_id}/reveal-password`
* **Trigger:** Menghasilkan audit log `CREDENTIAL_REVEAL`.
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

---

## 3. Modul Pengecekan Lapangan (DVR Checks)

### 3.1 Submit Hasil Checklist Lapangan
* **Endpoint:** `POST /api/v1/dvrs/{dvr_id}/checks`
* **Request Body:**
```json
{
  "is_ping_online": true,
  "is_time_synced": false,
  "time_difference_seconds": 320,
  "hdd_status": "Normal",
  "record_retention_days": 30,
  "camera_working_count": 8,
  "camera_broken_count": 0,
  "network_type": "WAN",
  "notes": "Jam DVR terlambat 5 menit, butuh sinkronisasi NTP manual."
}
```
* **Response `201 Created`:**
```json
{
  "success": true,
  "message": "Hasil checklist DVR berhasil dicatat.",
  "check_id": 4902
}
```