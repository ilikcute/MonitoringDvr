<template>
  <div class="space-y-6 animate-fadeIn">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Rekap &amp; Hasil Checklist Lapangan Toko
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Detail riwayat toko yang telah diinspeksi teknisi, waktu pengecekan (tanggal &amp; jam), dan temuan kendala fisik CCTV.
        </p>
      </div>

      <div class="flex items-center space-x-2">
        <!-- Tombol Export Excel -->
        <button
          @click="downloadChecklistExport('xlsx')"
          :disabled="isExporting"
          class="px-3.5 py-2 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-xs font-bold hover:bg-emerald-100 dark:hover:bg-emerald-900/60 flex items-center space-x-1.5 shadow-sm transition-all disabled:opacity-50 cursor-pointer"
          title="Ekspor laporan checklist lapangan ke spreadsheet Excel (.xlsx)"
        >
          <span v-if="isExporting" class="animate-spin h-3.5 w-3.5 border-2 border-emerald-600 border-t-transparent rounded-full"></span>
          <svg v-else class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>{{ isExporting ? 'Mengekspor...' : 'Export Excel' }}</span>
        </button>

        <button
          @click="refreshData"
          :disabled="isLoading"
          class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-1.5 shadow-sm transition-all"
        >
          <svg class="h-4 w-4" :class="{ 'animate-spin': isLoading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Refresh</span>
        </button>

        <router-link
          to="/stores"
          class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition-all"
        >
          <span>Pilih Toko untuk Cek Lapangan →</span>
        </router-link>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Checks -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Checklist</span>
          <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.total_checks ?? 0 }}</p>
        <span class="text-[11px] text-slate-400">Kali inspeksi tercatat</span>
      </div>

      <!-- Toko dengan Temuan / Issue -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Ditemukan Temuan</span>
          <div class="h-8 w-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ stats.total_with_issues ?? 0 }}</p>
        <span class="text-[11px] text-amber-600/80 dark:text-amber-400/80">Kamera rusak / HDD / NTP out</span>
      </div>

      <!-- Toko Kondisi Normal -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Kondisi Normal</span>
          <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ stats.total_normal ?? 0 }}</p>
        <span class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80">Semua parameter optimal</span>
      </div>

      <!-- Overdue DVRs (> 45 Hari) -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Overdue (&gt;45 Hari)</span>
          <div class="h-8 w-8 rounded-xl bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 flex items-center justify-center">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <p class="text-2xl font-extrabold text-red-600 dark:text-red-400 mt-2">{{ overdueDvrs.length }}</p>
        <span class="text-[11px] text-red-600/80 dark:text-red-400/80">Unit DVR perlu dikunjungi</span>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
      <div class="relative w-full md:w-80">
        <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="filters.search"
          type="text"
          class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Cari kode toko, nama toko, teknisi, temuan..."
          @input="debounceSearch"
        />
      </div>

      <div class="flex items-center space-x-3 w-full md:w-auto">
        <!-- Filter Hasil Temuan -->
        <select
          v-model="filters.has_issue"
          @change="fetchChecks"
          class="w-full md:w-52 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold"
        >
          <option value="all">Semua Hasil Checklist</option>
          <option value="yes">⚠️ Hanya Ada Temuan (Issue)</option>
          <option value="no">✓ Hanya Kondisi Normal</option>
        </select>

        <!-- Filter Ping Status -->
        <select
          v-model="filters.status"
          @change="fetchChecks"
          class="w-full md:w-40 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">Semua Status Ping</option>
          <option value="online">Ping Online</option>
          <option value="offline">Ping Offline (RTO)</option>
        </select>
      </div>
    </div>

    <!-- Main Content: Daftar Toko yang Sudah Di-Checklist & Hasil Temuan -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">
          Daftar Toko &amp; Riwayat Temuan Lapangan
        </h2>
        <span class="text-xs text-slate-400 font-semibold">
          Total: {{ pagination.total_records || 0 }} Rekaman
        </span>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="p-12 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
        <div class="inline-block animate-spin h-8 w-8 border-2 border-blue-600 border-t-transparent rounded-full mb-3"></div>
        <p class="text-sm font-medium">Memuat data rekap checklist &amp; temuan...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="checks.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200 dark:border-slate-800">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 mb-3">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Belum Ada Riwayat Checklist Lapangan</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
          Hasil checklist teknisi untuk toko ritel akan ditampilkan di sini beserta tanggal, jam, kondisi kamera, HDD, dan temuan kendala.
        </p>
        <router-link
          to="/stores"
          class="inline-block mt-4 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition-all"
        >
          Buka Direktori Toko &amp; Mulai Cek
        </router-link>
      </div>

      <!-- Desktop Table (>= 1024px) -->
      <div v-else class="hidden lg:block bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-x-auto shadow-sm">
        <table class="w-full min-w-[1020px] text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/60 dark:bg-slate-800/40">
              <th class="py-3.5 px-5 min-w-[240px]">Toko &amp; Unit DVR</th>
              <th class="py-3.5 px-4 whitespace-nowrap min-w-[160px]">Waktu Pengecekan (Tgl &amp; Jam)</th>
              <th class="py-3.5 px-4 whitespace-nowrap min-w-[120px]">Teknisi</th>
              <th class="py-3.5 px-4 min-w-[260px]">Hasil Temuan &amp; Kondisi</th>
              <th class="py-3.5 px-4 min-w-[180px]">Catatan Lapangan</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap min-w-[140px]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            <tr
              v-for="check in checks"
              :key="check.id"
              class="hover:bg-blue-50/40 dark:hover:bg-slate-800/50 transition-colors"
            >
              <!-- Toko & DVR -->
              <td class="py-3.5 px-5">
                <div class="flex items-center space-x-2">
                  <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 shrink-0">
                    {{ check.dvr?.store?.store_code || '-' }}
                  </span>
                  <span class="font-bold text-slate-800 dark:text-slate-100 truncate max-w-[200px]" :title="check.dvr?.store?.store_name">
                    {{ check.dvr?.store?.store_name || '-' }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-1.5">
                  <span class="shrink-0">{{ check.dvr?.store?.region || '-' }}</span>
                  <span>•</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[180px]" :title="`DVR ${check.dvr?.dvr_index} (${check.dvr?.label})`">
                    DVR {{ check.dvr?.dvr_index }} ({{ check.dvr?.label }})
                  </span>
                  <span>•</span>
                  <span class="font-mono text-slate-500 shrink-0">{{ check.dvr?.ip_address }}</span>
                </div>
              </td>

              <!-- Waktu Pengecekan -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-800 dark:text-slate-200">
                  {{ check.formatted_date_time || formatDate(check.check_timestamp) }}
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  Jaringan: <span class="font-semibold">{{ check.network_type || 'LAN' }}</span>
                </div>
              </td>

              <!-- Teknisi -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-semibold text-slate-800 dark:text-slate-200">
                  {{ check.checker?.name || 'Teknisi' }}
                </div>
                <span class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                  {{ check.checker?.role || 'Staff' }}
                </span>
              </td>

              <!-- Hasil Temuan & Kondisi -->
              <td class="py-3.5 px-4">
                <div class="space-y-1.5 max-w-[280px]">
                  <!-- Mini parameter badges -->
                  <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                    <!-- Ping -->
                    <span :class="check.is_ping_online ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'" class="px-2 py-0.5 rounded-full shrink-0">
                      {{ check.is_ping_online ? '✓ Ping OK' : '✕ RTO / Offline' }}
                    </span>

                    <!-- Kamera -->
                    <span :class="check.camera_broken_count > 0 ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 font-extrabold' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'" class="px-2 py-0.5 rounded-full shrink-0">
                      Kamera: {{ check.camera_working_count }} Normal / {{ check.camera_broken_count }} Rusak
                    </span>

                    <!-- HDD -->
                    <span :class="check.hdd_status === 'Normal' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'" class="px-2 py-0.5 rounded-full shrink-0">
                      HDD: {{ check.hdd_status }}
                    </span>

                    <!-- Jam NTP -->
                    <span :class="check.is_time_synced ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'" class="px-2 py-0.5 rounded-full shrink-0">
                      {{ check.is_time_synced ? 'Jam Sinkron' : `Deviasi ${check.time_difference_seconds}s` }}
                    </span>
                  </div>

                  <!-- Issue highlights -->
                  <div v-if="check.has_issues" class="p-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-[11px] text-amber-800 dark:text-amber-300 font-semibold flex items-center space-x-1.5">
                    <span class="font-extrabold shrink-0">⚠️ Temuan:</span>
                    <span>{{ check.issues?.join(' • ') }}</span>
                  </div>
                  <div v-else class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                    ✓ Kondisi prima, tidak ada kendala teknis.
                  </div>
                </div>
              </td>

              <!-- Catatan Lapangan -->
              <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 text-xs max-w-[200px]">
                <span v-if="check.notes" class="italic line-clamp-2" :title="check.notes">
                  "{{ check.notes }}"
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-6 text-right whitespace-nowrap min-w-[140px]">
                <router-link
                  v-if="check.dvr?.store?.id"
                  :to="`/stores/${check.dvr.store.id}`"
                  class="inline-flex items-center space-x-1 px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white font-bold text-xs transition-colors shrink-0 shadow-sm"
                >
                  <span>Detail Toko</span>
                  <span>→</span>
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards (< 1024px) -->
      <div class="lg:hidden space-y-3">
        <div
          v-for="check in checks"
          :key="check.id"
          class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3"
        >
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300">
                  {{ check.dvr?.store?.store_code || '-' }}
                </span>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ check.dvr?.store?.store_name || '-' }}
                </h3>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ check.dvr?.store?.region }} • DVR {{ check.dvr?.dvr_index }} ({{ check.dvr?.label }})
              </p>
            </div>

            <span :class="check.has_issues ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'" class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase">
              {{ check.has_issues ? 'Ada Temuan' : 'Normal' }}
            </span>
          </div>

          <!-- Waktu & Teknisi -->
          <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-xs space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-[11px]">Waktu Checklist:</span>
              <span class="font-bold text-slate-800 dark:text-white">{{ check.formatted_date_time || formatDate(check.check_timestamp) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-[11px]">Teknisi Lapangan:</span>
              <span class="font-semibold text-slate-800 dark:text-white">{{ check.checker?.name || '-' }}</span>
            </div>
          </div>

          <!-- Temuan Parameter -->
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-2 rounded-xl border" :class="check.is_ping_online ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-red-200 dark:border-red-800/60'">
              <span class="text-[10px] text-slate-400 block">Jaringan</span>
              <span class="font-bold" :class="check.is_ping_online ? 'text-emerald-600' : 'text-red-600'">
                {{ check.is_ping_online ? '✓ Online' : '✕ Offline' }}
              </span>
            </div>
            <div class="p-2 rounded-xl border" :class="check.camera_broken_count > 0 ? 'border-red-200 dark:border-red-800/60' : 'border-emerald-200 dark:border-emerald-800/60'">
              <span class="text-[10px] text-slate-400 block">Kamera</span>
              <span class="font-bold" :class="check.camera_broken_count > 0 ? 'text-red-600' : 'text-emerald-600'">
                {{ check.camera_working_count }} OK / {{ check.camera_broken_count }} Rusak
              </span>
            </div>
            <div class="p-2 rounded-xl border" :class="check.hdd_status === 'Normal' ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-red-200 dark:border-red-800/60'">
              <span class="text-[10px] text-slate-400 block">HDD</span>
              <span class="font-bold" :class="check.hdd_status === 'Normal' ? 'text-emerald-600' : 'text-red-600'">
                {{ check.hdd_status }}
              </span>
            </div>
            <div class="p-2 rounded-xl border" :class="check.is_time_synced ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-amber-200 dark:border-amber-800/60'">
              <span class="text-[10px] text-slate-400 block">Jam NTP</span>
              <span class="font-bold" :class="check.is_time_synced ? 'text-blue-600' : 'text-amber-600'">
                {{ check.is_time_synced ? 'Sinkron' : `${check.time_difference_seconds}s Out` }}
              </span>
            </div>
          </div>

          <!-- Highlight Issues if any -->
          <div v-if="check.has_issues" class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300">
            <span class="font-bold block text-[11px] mb-0.5">⚠️ Ringkasan Temuan:</span>
            <ul class="list-disc list-inside space-y-0.5">
              <li v-for="(iss, idx) in check.issues" :key="idx">{{ iss }}</li>
            </ul>
          </div>

          <!-- Catatan -->
          <p v-if="check.notes" class="text-xs text-slate-600 dark:text-slate-400 italic">
            "{{ check.notes }}"
          </p>

          <!-- Action Button -->
          <router-link
            v-if="check.dvr?.store?.id"
            :to="`/stores/${check.dvr.store.id}`"
            class="w-full h-10 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center space-x-1 shadow-sm transition-all"
          >
            <span>Buka Detail Toko &amp; DVR</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>
        </div>
      </div>

      <!-- Pagination Controls -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-xs">
        <span class="text-slate-500 dark:text-slate-400 font-medium">
          Halaman {{ pagination.current_page }} dari {{ pagination.last_page }} ({{ pagination.total_records }} total rekaman)
        </span>
        <div class="flex items-center space-x-2">
          <button
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 font-semibold disabled:opacity-40"
          >
            ← Sebelumnya
          </button>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 font-semibold disabled:opacity-40"
          >
            Selanjutnya →
          </button>
        </div>
      </div>
    </div>

    <!-- Overdue DVR Warning Section (BR-CHK-001: > 45 Hari) -->
    <div class="bg-amber-50/70 dark:bg-amber-950/40 rounded-3xl p-6 border border-amber-200 dark:border-amber-900/50 space-y-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-amber-700 dark:text-amber-400">
          <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <h2 class="text-base font-bold">Daftar DVR Berstatus Check Overdue (&gt; 45 Hari)</h2>
            <p class="text-xs text-amber-600/90 dark:text-amber-400/90">
              Sesuai aturan BR-CHK-001, DVR yang belum diperiksa lebih dari 45 hari memerlukan prioritas inspeksi teknisi.
            </p>
          </div>
        </div>

        <button
          @click="isOverdueExpanded = !isOverdueExpanded"
          class="text-xs font-bold text-amber-800 dark:text-amber-300 hover:underline"
        >
          {{ isOverdueExpanded ? 'Sembunyikan' : `Tampilkan (${overdueDvrs.length})` }}
        </button>
      </div>

      <div v-if="overdueDvrs.length === 0" class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold bg-emerald-50 dark:bg-emerald-950/60 p-3 rounded-2xl border border-emerald-200 dark:border-emerald-800">
        ✓ Seluruh unit DVR telah menjalani inspeksi rutin dalam 45 hari terakhir.
      </div>

      <div v-else-if="isOverdueExpanded" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="dvr in overdueDvrs"
          :key="dvr.id"
          class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-amber-200/80 dark:border-amber-900/60 shadow-sm flex items-center justify-between"
        >
          <div>
            <span class="font-mono text-xs font-bold text-amber-600 block">{{ dvr.store?.store_code }} — {{ dvr.store?.store_name }}</span>
            <span class="text-xs font-bold text-slate-800 dark:text-white mt-0.5 block">{{ dvr.label }}</span>
            <span class="text-[11px] text-slate-400">
              Terakhir dicek: {{ dvr.last_check_at ? formatDate(dvr.last_check_at) : 'Belum pernah dicek' }}
            </span>
          </div>

          <router-link
            :to="`/stores/${dvr.store_id}`"
            class="px-3 py-1.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-xs font-bold shrink-0 transition-colors"
          >
            Inspeksi →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Informasi Petunjuk Standar Inspeksi (BR-CHK-001 & BR-CHK-002) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600 dark:text-slate-400">
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-sm mb-1">Standar Waktu RTC/Jam NTP (BR-CHK-002)</h3>
        <p>
          Batas toleransi deviasi jam internal DVR dengan server NTP adalah <strong>180 detik (3 menit)</strong>. Jika selisih melebihi batas ini, sistem akan otomatis mencatat status <em>Time Out of Sync</em> untuk penyesuaian teknisi.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-sm mb-1">Pemeliharaan Berkala Kredensial</h3>
        <p>
          Pada saat kunjungan teknisi, periksa status fisik kamera, pastikan port HTTP/RTSP responsif, dan lakukan rotasi password akun departemen jika masa berlaku telah habis.
        </p>
      </div>
    </div>

    <!-- Toast Notification -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-2xl bg-slate-900/95 dark:bg-white/95 text-white dark:text-slate-900 text-xs font-semibold shadow-2xl border border-white/10 dark:border-slate-800/10 backdrop-blur-md transition-all animate-bounce"
    >
      {{ toastMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';

const checks = ref([]);
const stats = ref({
  total_checks: 0,
  total_with_issues: 0,
  total_normal: 0,
});
const overdueDvrs = ref([]);
const isLoading = ref(true);
const isExporting = ref(false);
const toastMessage = ref(null);
const isOverdueExpanded = ref(true);

const showToast = (msg) => {
  toastMessage.value = msg;
  setTimeout(() => {
    toastMessage.value = null;
  }, 4000);
};

const downloadChecklistExport = async (format = 'xlsx') => {
  if (pagination.value.total_records === 0 || checks.value.length === 0) {
    showToast('⚠️ Data riwayat checklist masih kosong atau tidak ditemukan.');
    return;
  }

  isExporting.value = true;
  try {
    const params = { format };
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.has_issue !== 'all') params.has_issue = filters.value.has_issue;
    if (filters.value.status !== 'all') params.status = filters.value.status;

    const response = await api.get('/checks/export', {
      params,
      responseType: 'blob',
    });

    if (response.data.type === 'application/json') {
      const text = await response.data.text();
      const json = JSON.parse(text);
      showToast(`⚠️ ${json.message || 'Gagal mengekspor data checklist.'}`);
      return;
    }

    const blob = new Blob([response.data], {
      type: format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', `CDAMS_Checklist_Report_${new Date().toISOString().slice(0, 10)}.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);
    showToast(`✓ Berkas Rekap Checklist (${format.toUpperCase()}) berhasil diunduh.`);
  } catch (error) {
    let errorMsg = 'Gagal mengunduh file rekap checklist.';
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const json = JSON.parse(text);
        errorMsg = json.message || errorMsg;
      } catch (_) {}
    } else if (error.response?.data?.message) {
      errorMsg = error.response.data.message;
    }
    showToast(`✕ ${errorMsg}`);
  } finally {
    isExporting.value = false;
  }
};

const filters = ref({
  search: '',
  has_issue: 'all',
  status: 'all',
  page: 1,
});

const pagination = ref({
  current_page: 1,
  per_page: 15,
  total_records: 0,
  last_page: 1,
});

let debounceTimer = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.value.page = 1;
    fetchChecks();
  }, 250);
};

const fetchChecks = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: filters.value.page,
    };
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.has_issue !== 'all') params.has_issue = filters.value.has_issue;
    if (filters.value.status !== 'all') params.status = filters.value.status;

    const response = await api.get('/checks', { params });
    checks.value = response.data.data;
    pagination.value = response.data.meta;
    if (response.data.stats) {
      stats.value = response.data.stats;
    }
  } catch (error) {
    console.error('Gagal memuat riwayat checklist:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchOverdue = async () => {
  try {
    const response = await api.get('/checks/overdue');
    overdueDvrs.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat daftar overdue:', error);
  }
};

const goToPage = (page) => {
  filters.value.page = page;
  fetchChecks();
};

const refreshData = () => {
  fetchChecks();
  fetchOverdue();
};

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }) + ' WIB';
};

onMounted(() => {
  fetchChecks();
  fetchOverdue();
});
</script>
