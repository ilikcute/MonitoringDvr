<template>
  <div class="space-y-8 animate-fadeIn">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Dashboard Monitoring & Aset
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
          Sentralisasi inventaris CCTV DVR dan kontrol 5 akun departemen internal (~666 toko).
        </p>
      </div>

      <!-- Quick Action Buttons -->
      <div class="flex items-center space-x-3">
        <router-link
          to="/stores"
          class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs sm:text-sm shadow-md shadow-blue-500/20 transition-all"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <span>Direktori Toko</span>
        </router-link>

        <router-link
          to="/checks"
          class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 font-semibold text-xs sm:text-sm text-slate-700 dark:text-slate-200 shadow-sm transition-all"
        >
          <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <span>Checklist Lapangan</span>
        </router-link>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-6">
      <!-- Total Toko -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Toko</span>
          <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
        </div>
        <div class="mt-4 flex items-baseline space-x-2">
          <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ stats.stores.total }}</span>
          <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">{{ stats.stores.active }} Aktif</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">
          Target skala: ~666 gerai ritel
        </div>
      </div>

      <!-- Toko Sudah Dicek -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Toko Sudah Dicek</span>
          <div class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
        <div class="mt-4 flex items-baseline space-x-2">
          <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ stats.stores.checked_count ?? stats.checklists.checked_stores_count ?? 0 }}</span>
          <span class="text-xs text-teal-600 dark:text-teal-400 font-semibold">{{ stats.stores.checked_percentage ?? stats.checklists.checked_stores_percentage ?? 0 }}% Toko</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">
          {{ stats.checklists.checked_stores_this_month ?? 0 }} toko diperiksa bulan ini
        </div>
      </div>

      <!-- Total DVR -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Perangkat DVR</span>
          <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
        </div>
        <div class="mt-4 flex items-baseline space-x-2">
          <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ stats.dvrs.total }}</span>
          <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">{{ stats.dvrs.online }} Online</span>
        </div>
        <div class="mt-2 text-xs text-slate-400 flex items-center space-x-2">
          <span class="text-red-500 font-medium">{{ stats.dvrs.offline }} Offline</span>
          <span>•</span>
          <span class="text-amber-500 font-medium">{{ stats.dvrs.degraded }} Kendala</span>
        </div>
      </div>

      <!-- Checklist Bulan Ini -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inspeksi Bulan Ini</span>
          <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="mt-4 flex items-baseline space-x-2">
          <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ stats.checklists.this_month }}</span>
          <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold">Kunjungan</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">
          Standar BR-CHK-001: min. 1x / 30 hari
        </div>
      </div>

      <!-- Overdue Checks Alert -->
      <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Check Overdue</span>
          <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="mt-4 flex items-baseline space-x-2">
          <span class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ stats.checklists.overdue_count }}</span>
          <span class="text-xs text-slate-400 font-semibold">Unit &gt; 45 Hari</span>
        </div>
        <div class="mt-2 text-xs text-slate-400">
          Memerlukan jadwal kunjungan teknisi
        </div>
      </div>
    </div>

    <!-- Live Network Ping Utility Banner & Quick DVR Ping Tool -->
    <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white shadow-xl relative overflow-hidden space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-500/30 text-blue-200 border border-blue-400/30 mb-2">
            Dedicated Network Ping Tool
          </span>
          <h3 class="text-lg font-bold">Uji Konektivitas Server HO & Ping DVR Toko</h3>
          <p class="text-xs text-blue-200 mt-1 max-w-2xl">
            IP <span class="font-mono font-bold text-white bg-blue-800/80 px-1.5 py-0.5 rounded">192.168.25.200</span> merupakan Server Head Office yang beroperasi 24 jam sebagai acuan link jaringan. Anda juga dapat mengetik alamat IP DVR toko mana pun di bawah ini untuk pengujian respon instan dari server.
          </p>
        </div>

        <!-- Quick Button Server HO -->
        <div class="shrink-0 flex items-center space-x-2">
          <PingTestButton ip="192.168.25.200" label="Test Ping Server HO (192.168.25.200)" />
        </div>
      </div>

      <!-- Custom DVR IP Ping Box -->
      <div class="pt-3 border-t border-white/10 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
          </div>
          <input
            v-model="customPingIp"
            type="text"
            placeholder="Ketik alamat IP DVR toko yang ingin diuji (contoh: 10.10.1.200)..."
            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-white/10 border border-white/20 text-white placeholder-blue-300/70 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white/20 transition-all font-mono"
            @keyup.enter="runCustomPing"
          />
        </div>

        <button
          type="button"
          :disabled="isCustomPinging || !customPingIp.trim()"
          class="inline-flex items-center justify-center space-x-2 px-5 py-2.5 rounded-xl bg-blue-500 hover:bg-blue-400 disabled:opacity-50 text-white text-xs font-semibold shadow-md transition-all shrink-0 cursor-pointer"
          @click="runCustomPing"
        >
          <span v-if="isCustomPinging" class="animate-spin h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full"></span>
          <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span>{{ isCustomPinging ? 'Menguji Ping...' : 'Uji Ping IP DVR' }}</span>
        </button>

        <!-- Result Badge if tested -->
        <div v-if="customPingResult" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl bg-black/40 border border-white/10 text-xs shrink-0 animate-fadeIn">
          <span
            :class="customPingResult.is_online ? 'bg-emerald-400' : 'bg-red-400'"
            class="h-2.5 w-2.5 rounded-full inline-block animate-pulse"
          ></span>
          <span :class="customPingResult.is_online ? 'text-emerald-300 font-bold' : 'text-red-300 font-bold'">
            {{ customPingResult.status }} {{ customPingResult.latency_ms ? `(${customPingResult.latency_ms}ms)` : '' }}
          </span>
          <span class="text-[11px] text-blue-200 hidden md:inline">• {{ customPingResult.ip }}</span>
        </div>
      </div>
    </div>

    <!-- Two Columns: Regional Distribution & Recent Security Audit Logs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Regional Distribution -->
      <div class="bg-white dark:bg-slate-900 p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
          <span>Sebaran Toko Berdasarkan Wilayah</span>
          <span class="text-xs font-normal text-slate-400">{{ stats.regional_distribution.length }} Region</span>
        </h3>

        <div v-if="stats.regional_distribution.length === 0" class="py-8 text-center text-xs text-slate-400">
          Belum ada data toko terdaftar. Gunakan menu Impor Spreadsheet di Direktori Toko.
        </div>

        <div v-else class="space-y-4">
          <div v-for="item in stats.regional_distribution" :key="item.region" class="space-y-1">
            <div class="flex justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
              <span>{{ item.region }}</span>
              <span class="font-mono">{{ item.count }} Toko</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
              <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(100, (item.count / Math.max(1, stats.stores.total)) * 100)}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Security Audit Trail -->
      <div class="bg-white dark:bg-slate-900 p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Aktivitas Audit Keamanan Terakhir</h3>
          <router-link v-if="authStore.isSuperAdmin" to="/audit-logs" class="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline">
            Lihat Semua Log →
          </router-link>
        </div>

        <div v-if="stats.recent_logs.length === 0" class="py-8 text-center text-xs text-slate-400">
          Belum ada riwayat audit keamanan tercatat.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="log in stats.recent_logs"
            :key="log.id"
            class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs"
          >
            <div>
              <div class="flex items-center space-x-2">
                <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ log.action }}</span>
                <span
                  :class="log.network_type === 'LAN' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'"
                  class="px-2 py-0.2 text-[10px] rounded-full font-bold"
                >
                  {{ log.network_type }}
                </span>
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">
                Oleh {{ log.user_name }} • Target: {{ log.target }}
              </p>
            </div>
            <span class="text-[10px] text-slate-400 font-mono">
              {{ formatDate(log.created_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';
import { useAuthStore } from '@/stores/auth';
import PingTestButton from '@/components/PingTestButton.vue';

const authStore = useAuthStore();

const stats = ref({
  stores: { total: 0, active: 0, renovation: 0, closed: 0, checked_count: 0, checked_percentage: 0 },
  dvrs: { total: 0, online: 0, offline: 0, degraded: 0, maintenance: 0 },
  checklists: { this_month: 0, overdue_count: 0, checked_stores_count: 0, checked_stores_this_month: 0, checked_stores_percentage: 0 },
  regional_distribution: [],
  recent_logs: [],
});

const customPingIp = ref('');
const isCustomPinging = ref(false);
const customPingResult = ref(null);

const runCustomPing = async () => {
  const ip = customPingIp.value.trim();
  if (!ip) return;

  isCustomPinging.value = true;
  customPingResult.value = null;

  try {
    const response = await api.post('/dvrs/ping-test', {
      ip_address: ip,
      port: 80,
    });
    customPingResult.value = response.data.data;
  } catch (error) {
    customPingResult.value = {
      is_online: false,
      status: 'Offline',
      latency_ms: null,
      ip: ip,
      message: 'Gagal mengeksekusi ping test dari server.',
    };
  } finally {
    isCustomPinging.value = false;
  }
};

const fetchDashboard = async () => {
  try {
    const response = await api.get('/dashboard');
    stats.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat statistik dashboard:', error);
  }
};

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const date = new Date(isoString);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  fetchDashboard();
});
</script>
