<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LAN Subnets
    |--------------------------------------------------------------------------
    | Daftar blok IP CIDR privat yang diidentifikasi sebagai jaringan LAN
    | Kantor Pusat atau Cabang.
    */
    'lan_subnets' => array_filter(array_map('trim', explode(',', env('LAN_SUBNETS', '127.0.0.1/32,10.0.0.0/8,192.168.25.0/24,::1/128')))),

    /*
    |--------------------------------------------------------------------------
    | Default Whitelisted Ping IP
    |--------------------------------------------------------------------------
    | IP default yang ter-whitelist ke semua toko untuk pengujian konektivitas
    */
    'default_ping_ip' => env('DVR_DEFAULT_PING_IP', '192.168.25.200'),
    'ping_timeout_ms' => (int) env('DVR_PING_TIMEOUT_MS', 1500),

    /*
    |--------------------------------------------------------------------------
    | Business Rules Thresholds
    |--------------------------------------------------------------------------
    */
    'max_dvrs_per_store' => 2,
    'ntp_sync_threshold_seconds' => 180, // BR-CHK-002: > 180 detik = Out of Sync
    'check_overdue_days' => 45,          // BR-CHK-001: > 45 hari = Overdue
    'check_warning_days' => 30,          // Minimal 1x per 30 hari
];
