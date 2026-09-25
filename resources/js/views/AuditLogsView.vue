<template>
  <div class="space-y-6 animate-fadeIn">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Audit Trail &amp; Keamanan Sistem
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Catatan aktivitas append-only (anti-tampering) untuk pemantauan kredensial &amp; konfigurasi.
        </p>
      </div>

      <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 text-xs font-bold">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span>Immutability Protected (365 Hari)</span>
      </span>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-wrap gap-3 items-center">
      <!-- Filter Aksi -->
      <select
        v-model="filters.action"
        @change="fetchLogs"
        class="text-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none"
      >
        <option value="">Semua Event Aksi</option>
        <option value="CREDENTIAL_REVEAL">CREDENTIAL_REVEAL (Intip Password)</option>
        <option value="AUTH_LOGIN_SUCCESS">AUTH_LOGIN_SUCCESS (Login Berhasil)</option>
        <option value="AUTH_LOGIN_FAILED">AUTH_LOGIN_FAILED (Login Gagal)</option>
        <option value="DVR_IP_CHANGED">DVR_IP_CHANGED (Perubahan IP/Port)</option>
        <option value="ACCOUNT_PASSWORD_CHANGED">ACCOUNT_PASSWORD_CHANGED</option>
        <option value="MASS_DATA_EXPORT">MASS_DATA_EXPORT (Ekspor Data)</option>
        <option value="DVR_CHECK_SUBMITTED">DVR_CHECK_SUBMITTED (Checklist)</option>
      </select>

      <!-- Filter Network -->
      <select
        v-model="filters.network_type"
        @change="fetchLogs"
        class="text-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none"
      >
        <option value="">Semua Jaringan (LAN &amp; WAN)</option>
        <option value="LAN">Hanya Jaringan LAN (HO)</option>
        <option value="WAN">Hanya Jaringan WAN (Toko/VPN)</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="p-12 text-center text-slate-400">
      <div class="inline-block animate-spin h-7 w-7 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs">Memuat riwayat audit log...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="logs.length === 0" class="p-12 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
      Tidak ada riwayat log keamanan yang cocok dengan kriteria filter.
    </div>

    <!-- Audit Log Table -->
    <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/60 dark:bg-slate-800/40">
            <th class="py-3 px-5">Waktu</th>
            <th class="py-3 px-4">Event Aksi</th>
            <th class="py-3 px-4">Pengguna</th>
            <th class="py-3 px-4">Mode Jaringan</th>
            <th class="py-3 px-4">IP &amp; User Agent</th>
            <th class="py-3 px-5 text-right">Rincian</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
          <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <!-- Timestamp -->
            <td class="py-3 px-5 font-mono text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
              {{ formatDateTime(log.created_at) }}
            </td>

            <!-- Action -->
            <td class="py-3 px-4 font-mono font-bold whitespace-nowrap">
              <span :class="actionBadgeClass(log.action)" class="px-2 py-0.5 rounded-lg text-[10px] inline-block font-mono">
                {{ log.action }}
              </span>
            </td>

            <!-- User -->
            <td class="py-3 px-4 whitespace-nowrap">
              <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ log.user?.name || 'System / Tamu' }}</span>
              <span class="text-[10px] text-slate-400 font-mono">{{ log.user?.email || '-' }}</span>
            </td>

            <!-- Network Type -->
            <td class="py-3 px-4 whitespace-nowrap">
              <span
                :class="log.network_type === 'LAN' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'"
                class="px-2 py-0.5 text-[10px] rounded-full font-bold uppercase"
              >
                {{ log.network_type }}
              </span>
            </td>

            <!-- IP & Agent -->
            <td class="py-3 px-4 whitespace-nowrap">
              <span class="font-mono text-slate-700 dark:text-slate-300 block">{{ log.ip_address }}</span>
              <span class="text-[10px] text-slate-400 truncate max-w-xs block" :title="log.user_agent">{{ log.user_agent }}</span>
            </td>

            <!-- Detail Modal Trigger -->
            <td class="py-3 px-5 text-right whitespace-nowrap">
              <button
                @click="selectedLog = log"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-slate-800"
              >
                Lihat Payload
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Payload Detail Modal -->
    <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-3">
          <h3 class="font-bold text-sm text-slate-900 dark:text-white font-mono">{{ selectedLog.action }} #{{ selectedLog.id }}</h3>
          <button @click="selectedLog = null" class="text-slate-400 hover:text-slate-500">✕</button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <span class="font-bold text-slate-400 block mb-1">Payload Nilai Baru:</span>
            <pre class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(selectedLog.new_values, null, 2) || '-' }}</pre>
          </div>
          <div v-if="selectedLog.old_values">
            <span class="font-bold text-slate-400 block mb-1">Payload Nilai Lama:</span>
            <pre class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button @click="selectedLog = null" class="px-4 py-1.5 text-xs font-bold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';

const logs = ref([]);
const isLoading = ref(true);
const selectedLog = ref(null);

const filters = ref({
  action: '',
  network_type: '',
});

const fetchLogs = async () => {
  isLoading.value = true;
  try {
    const response = await api.get('/audit-logs', {
      params: filters.value,
    });
    logs.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat audit log:', error);
  } finally {
    isLoading.value = false;
  }
};

const actionBadgeClass = (action) => {
  if (action === 'CREDENTIAL_REVEAL') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300';
  if (action === 'AUTH_LOGIN_SUCCESS') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300';
  if (action === 'AUTH_LOGIN_FAILED') return 'bg-red-100 text-red-800 dark:bg-red-950/80 dark:text-red-300';
  if (action === 'MASS_DATA_EXPORT') return 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300';
  return 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300';
};

const formatDateTime = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

onMounted(() => {
  fetchLogs();
});
</script>
