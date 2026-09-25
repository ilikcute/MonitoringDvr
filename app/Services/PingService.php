<?php

namespace App\Services;

class PingService
{
    /**
     * Jalankan uji ping ke IP (default 192.168.25.200).
     * Reply -> Online
     * RTO / Unreachable -> Offline
     */
    public function ping(?string $ip = null, ?int $port = null): array
    {
        $targetIp = $ip ?: config('cdams.default_ping_ip', '192.168.25.200');
        $timeoutMs = config('cdams.ping_timeout_ms', 1500);

        // Validasi format IPv4
        if (!filter_var($targetIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return [
                'ip' => $targetIp,
                'status' => 'Offline',
                'is_online' => false,
                'latency_ms' => null,
                'raw_output' => 'Invalid IPv4 address format.',
                'message' => 'Format IP tidak valid.',
            ];
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows
            ? "ping -n 1 -w {$timeoutMs} " . escapeshellarg($targetIp)
            : "ping -c 1 -W " . ceil($timeoutMs / 1000) . " " . escapeshellarg($targetIp);

        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);
        $rawOutput = implode("\n", $output);

        $isOnline = false;
        $latencyMs = null;

        if ($isWindows) {
            // Pada Windows: cek apakah ada "Reply from" dan BUKAN "Destination host unreachable"
            if (
                preg_match('/Reply from/i', $rawOutput) &&
                !preg_match('/Destination host unreachable/i', $rawOutput) &&
                !preg_match('/Request timed out/i', $rawOutput)
            ) {
                $isOnline = true;
                if (preg_match('/time[=<](\d+)ms/i', $rawOutput, $matches)) {
                    $latencyMs = (int) $matches[1];
                }
            }
        } else {
            // Pada Linux / Unix
            if ($returnCode === 0 && preg_match('/bytes from/i', $rawOutput)) {
                $isOnline = true;
                if (preg_match('/time=([\d\.]+)\s*ms/i', $rawOutput, $matches)) {
                    $latencyMs = (int) round((float) $matches[1]);
                }
            }
        }

        // Jika port disediakan dan ICMP belum online atau ingin verifikasi port
        if (!$isOnline && $port && $port > 0) {
            $connection = @fsockopen($targetIp, $port, $errno, $errstr, (float) ($timeoutMs / 1000));
            if (is_resource($connection)) {
                $isOnline = true;
                fclose($connection);
            }
        }

        return [
            'ip' => $targetIp,
            'status' => $isOnline ? 'Online' : 'Offline',
            'is_online' => $isOnline,
            'latency_ms' => $latencyMs,
            'raw_output' => $rawOutput,
            'message' => $isOnline
                ? "Ping Reply sukses dari {$targetIp} (Latency: " . ($latencyMs !== null ? "{$latencyMs}ms" : '<1ms') . ")"
                : "Ping RTO (Request Timed Out) ke {$targetIp}. Perangkat berstatus Offline.",
        ];
    }
}
