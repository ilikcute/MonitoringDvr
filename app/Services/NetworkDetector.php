<?php

namespace App\Services;

use Illuminate\Http\Request;

class NetworkDetector
{
    /**
     * Mendeteksi apakah request berasal dari LAN (Head Office) atau WAN (Toko).
     * Mendukung header simulasi 'X-Network-Simulate' untuk keperluan pengujian.
     */
    public function detect(Request $request): string
    {
        // 1. Dukungan simulasi header untuk keperluan testing/demo
        $simulate = $request->header('X-Network-Simulate') ?? $request->query('network_simulate');
        if ($simulate && in_array(strtoupper($simulate), ['LAN', 'WAN'], true)) {
            return strtoupper($simulate);
        }

        $clientIp = $request->ip();

        if (empty($clientIp)) {
            return 'WAN';
        }

        // Localhost checking
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true)) {
            return 'LAN';
        }

        $lanSubnets = config('cdams.lan_subnets', []);

        foreach ($lanSubnets as $subnet) {
            if ($this->ipInCidr($clientIp, $subnet)) {
                return 'LAN';
            }
        }

        return 'WAN';
    }

    /**
     * Helper untuk memverifikasi apakah IP berada dalam blok CIDR tertentu.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, ':') || str_contains($ip, ':')) {
            // IPv6 exact match
            return $ip === explode('/', $cidr)[0];
        }

        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $netmask = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }
}
