<template>
  <div class="space-y-6 animate-fadeIn">
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Direktori Toko & DVR
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Manajemen data ~666 gerai ritel dan pemetaan status perangkat CCTV.
        </p>
      </div>

      <div class="flex items-center space-x-2.5">
        <!-- Export Button -->
        <button
          @click="initiateExport('xlsx')"
          :disabled="isExporting"
          class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-1.5 shadow-sm transition-all disabled:opacity-50"
        >
          <span v-if="isExporting" class="animate-spin h-3.5 w-3.5 border-2 border-emerald-600 border-t-transparent rounded-full mr-1"></span>
          <svg v-else class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor Excel' }}</span>
        </button>

        <!-- Template Impor Button -->
        <button
          @click="downloadTemplateFile('xlsx')"
          :disabled="isDownloadingTemplate"
          class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-1.5 shadow-sm transition-all disabled:opacity-50"
          title="Unduh format template Excel untuk persiapan data impor"
        >
          <span v-if="isDownloadingTemplate" class="animate-spin h-3.5 w-3.5 border-2 border-purple-600 border-t-transparent rounded-full mr-1"></span>
          <svg v-else class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>{{ isDownloadingTemplate ? 'Mengunduh...' : 'Template Impor' }}</span>
        </button>

        <!-- Import Button (EDP & Super Admin) -->
        <button
          v-if="authStore.isSuperAdmin || authStore.isTechnician"
          @click="isImportOpen = true"
          class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-1.5 shadow-sm transition-all"
        >
          <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
          </svg>
          <span>Impor Data</span>
        </button>

        <!-- Tambah Toko Button (EDP & Super Admin) -->
        <button
          v-if="authStore.isSuperAdmin || authStore.isTechnician"
          @click="openAddStoreModal"
          class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 flex items-center space-x-1.5 transition-all"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>+ Tambah Toko</span>
        </button>
      </div>
    </div>

    <!-- Quick Filters Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
      <div class="relative w-full md:w-80">
        <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="filters.search"
          type="text"
          class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Cari kode toko atau nama toko..."
          @input="debounceSearch"
        />
      </div>

      <div class="flex items-center space-x-3 w-full md:w-auto">
        <!-- Region Filter -->
        <select
          v-model="filters.region"
          @change="fetchStores"
          class="w-full md:w-44 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="All">Semua Wilayah</option>
          <option v-for="reg in availableRegions" :key="reg" :value="reg">{{ reg }}</option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="filters.status"
          @change="fetchStores"
          class="w-full md:w-36 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="All">Semua Status</option>
          <option value="Active">Active</option>
          <option value="Renovation">Renovation</option>
          <option value="Closed">Closed</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="p-12 text-center text-slate-400">
      <div class="inline-block animate-spin h-8 w-8 border-2 border-blue-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-sm font-medium">Memuat data direktori toko...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="stores.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200 dark:border-slate-800">
      <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 mb-3">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
      <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Belum Ada Data Toko</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
        Data toko masih kosong atau belum ditemukan berdasarkan kriteria filter saat ini.
      </p>
      <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
        <button
          @click="downloadTemplateFile('xlsx')"
          :disabled="isDownloadingTemplate"
          class="px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center space-x-1.5 shadow-sm transition-all"
        >
          <svg class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Unduh Template Excel</span>
        </button>
        <button
          v-if="authStore.isSuperAdmin || authStore.isTechnician"
          @click="isImportOpen = true"
          class="px-3.5 py-2 text-xs font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center space-x-1.5 shadow-sm transition-all"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
          </svg>
          <span>Impor Data dari Excel</span>
        </button>
      </div>
    </div>

    <!-- Desktop Dense Data Table (>= 1024px) -->
    <div v-else class="hidden lg:block bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-x-auto shadow-sm">
      <table class="w-full min-w-[960px] text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/60 dark:bg-slate-800/40">
            <th class="py-3.5 px-5">Kode</th>
            <th class="py-3.5 px-4">Nama Toko</th>
            <th class="py-3.5 px-4">Wilayah</th>
            <th class="py-3.5 px-4 min-w-[220px]">DVR 1 (Status &amp; Cek Terakhir)</th>
            <th class="py-3.5 px-4 min-w-[220px]">DVR 2 (Status &amp; Cek Terakhir)</th>
            <th class="py-3.5 px-4">Akun Ready</th>
            <th class="py-3.5 px-6 text-right min-w-[190px]">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
          <tr
            v-for="store in stores"
            :key="store.id"
            class="hover:bg-blue-50/40 dark:hover:bg-slate-800/50 transition-colors group cursor-pointer"
            @click="$router.push(`/stores/${store.id}`)"
          >
            <!-- Kode -->
            <td class="py-3.5 px-5 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
              {{ store.store_code }}
            </td>

            <!-- Nama Toko -->
            <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-100 whitespace-nowrap">
              {{ store.store_name }}
              <span
                :class="statusBadge(store.status)"
                class="ml-2 px-1.5 py-0.5 text-[9px] rounded-full font-bold uppercase"
              >
                {{ store.status }}
              </span>
            </td>

            <!-- Wilayah -->
            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
              {{ store.region }}
            </td>

            <!-- DVR 1 -->
            <td class="py-3 px-4 min-w-[220px]">
              <div v-if="getDvr(store, 1)" class="space-y-1.5">
                <div class="flex items-center space-x-1.5">
                  <span :class="dvrStatusDot(getDvr(store, 1).status)" class="h-2 w-2 rounded-full shrink-0"></span>
                  <span class="font-mono text-slate-700 dark:text-slate-300 font-medium">
                    {{ getDvr(store, 1).ip_address }}
                  </span>
                  <span :class="dvrStatusPill(getDvr(store, 1).status)" class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase">
                    {{ getDvr(store, 1).status }}
                  </span>
                </div>

                <!-- Hasil Cek Terakhir -->
                <div v-if="getDvr(store, 1).latest_check" class="space-y-1">
                  <div class="flex items-center space-x-1.5">
                    <span
                      :class="getDvr(store, 1).latest_check.has_issues
                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800'
                        : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800'"
                      class="px-2 py-0.5 text-[9px] font-bold rounded-lg border inline-flex items-center space-x-1"
                    >
                      <span v-if="getDvr(store, 1).latest_check.has_issues">⚠️ {{ getDvr(store, 1).latest_check.issues?.join(' • ') || 'Ada Temuan' }}</span>
                      <span v-else>✓ Normal (OK)</span>
                    </span>

                    <button
                      type="button"
                      @click.stop="openChecklistModal(getDvr(store, 1), store)"
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 hover:bg-blue-600 hover:text-white transition-colors"
                      title="Catat Checklist Baru untuk DVR 1"
                    >
                      + Cek
                    </button>
                  </div>

                  <div class="text-[10px] text-slate-500 dark:text-slate-400 space-y-0.5 leading-tight">
                    <div>
                      🕒 {{ getDvr(store, 1).latest_check.formatted_date_time || formatDate(getDvr(store, 1).latest_check.check_timestamp) }}
                    </div>
                    <div class="flex items-center space-x-1 text-[9px]">
                      <span>Kam: <strong :class="getDvr(store, 1).latest_check.camera_broken_count > 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'">{{ getDvr(store, 1).latest_check.camera_working_count }} OK<span v-if="getDvr(store, 1).latest_check.camera_broken_count > 0">, {{ getDvr(store, 1).latest_check.camera_broken_count }} Rusak</span></strong></span>
                      <span>•</span>
                      <span>HDD: <strong :class="getDvr(store, 1).latest_check.hdd_status !== 'Normal' ? 'text-red-500' : 'text-slate-600 dark:text-slate-300'">{{ getDvr(store, 1).latest_check.hdd_status }}</strong></span>
                    </div>
                  </div>
                </div>

                <!-- Belum Ada Checklist -->
                <div v-else class="flex items-center space-x-1.5">
                  <span class="text-[10px] text-slate-400 italic">Belum dicek</span>
                  <button
                    type="button"
                    @click.stop="openChecklistModal(getDvr(store, 1), store)"
                    class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white transition-colors"
                  >
                    + Catat Cek
                  </button>
                </div>
              </div>
              <span v-else class="text-slate-400">-</span>
            </td>

            <!-- DVR 2 -->
            <td class="py-3 px-4 min-w-[220px]">
              <div v-if="getDvr(store, 2)" class="space-y-1.5">
                <div class="flex items-center space-x-1.5">
                  <span :class="dvrStatusDot(getDvr(store, 2).status)" class="h-2 w-2 rounded-full shrink-0"></span>
                  <span class="font-mono text-slate-700 dark:text-slate-300 font-medium">
                    {{ getDvr(store, 2).ip_address }}
                  </span>
                  <span :class="dvrStatusPill(getDvr(store, 2).status)" class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase">
                    {{ getDvr(store, 2).status }}
                  </span>
                </div>

                <!-- Hasil Cek Terakhir -->
                <div v-if="getDvr(store, 2).latest_check" class="space-y-1">
                  <div class="flex items-center space-x-1.5">
                    <span
                      :class="getDvr(store, 2).latest_check.has_issues
                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800'
                        : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800'"
                      class="px-2 py-0.5 text-[9px] font-bold rounded-lg border inline-flex items-center space-x-1"
                    >
                      <span v-if="getDvr(store, 2).latest_check.has_issues">⚠️ {{ getDvr(store, 2).latest_check.issues?.join(' • ') || 'Ada Temuan' }}</span>
                      <span v-else>✓ Normal (OK)</span>
                    </span>

                    <button
                      type="button"
                      @click.stop="openChecklistModal(getDvr(store, 2), store)"
                      class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 hover:bg-blue-600 hover:text-white transition-colors"
                      title="Catat Checklist Baru untuk DVR 2"
                    >
                      + Cek
                    </button>
                  </div>

                  <div class="text-[10px] text-slate-500 dark:text-slate-400 space-y-0.5 leading-tight">
                    <div>
                      🕒 {{ getDvr(store, 2).latest_check.formatted_date_time || formatDate(getDvr(store, 2).latest_check.check_timestamp) }}
                    </div>
                    <div class="flex items-center space-x-1 text-[9px]">
                      <span>Kam: <strong :class="getDvr(store, 2).latest_check.camera_broken_count > 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'">{{ getDvr(store, 2).latest_check.camera_working_count }} OK<span v-if="getDvr(store, 2).latest_check.camera_broken_count > 0">, {{ getDvr(store, 2).latest_check.camera_broken_count }} Rusak</span></strong></span>
                      <span>•</span>
                      <span>HDD: <strong :class="getDvr(store, 2).latest_check.hdd_status !== 'Normal' ? 'text-red-500' : 'text-slate-600 dark:text-slate-300'">{{ getDvr(store, 2).latest_check.hdd_status }}</strong></span>
                    </div>
                  </div>
                </div>

                <!-- Belum Ada Checklist -->
                <div v-else class="flex items-center space-x-1.5">
                  <span class="text-[10px] text-slate-400 italic">Belum dicek</span>
                  <button
                    type="button"
                    @click.stop="openChecklistModal(getDvr(store, 2), store)"
                    class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white transition-colors"
                  >
                    + Catat Cek
                  </button>
                </div>
              </div>
              <span v-else class="text-slate-400">-</span>
            </td>

            <!-- Akun Ready Ratio -->
            <td class="py-3.5 px-4 whitespace-nowrap font-mono font-semibold text-slate-700 dark:text-slate-300">
              <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs">
                {{ store.accounts_ratio }}
              </span>
            </td>

            <!-- Aksi -->
            <td class="py-3.5 px-6 text-right whitespace-nowrap min-w-[190px]" @click.stop>
              <div class="flex items-center justify-end space-x-1.5">
                <button
                  v-if="store.dvrs?.length > 0"
                  type="button"
                  @click="openChecklistModal(store.dvrs[0], store)"
                  class="px-2.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-600 dark:hover:text-white font-bold text-xs transition-colors flex items-center space-x-1 shadow-sm"
                  title="Catat Checklist Inspeksi Teknisi"
                >
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                  </svg>
                  <span>Checklist</span>
                </button>
                <router-link
                  :to="`/stores/${store.id}`"
                  class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white font-bold text-xs transition-colors shrink-0 shadow-sm"
                >
                  <span>Detail &amp; Akun</span>
                  <span>→</span>
                </router-link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card-Based Layout (< 1024px) -->
    <div class="lg:hidden space-y-3">
      <div
        v-for="store in stores"
        :key="store.id"
        class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3"
      >
        <!-- Header Kartu: Kode & Nama + Status -->
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center space-x-2">
              <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300">
                {{ store.store_code }}
              </span>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ store.store_name }}
              </h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ store.region }} • Akun Ready: {{ store.accounts_ratio }}
            </p>
          </div>
          <span :class="statusBadge(store.status)" class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase">
            {{ store.status }}
          </span>
        </div>

        <!-- DVR Segments -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-800">
          <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-[10px] uppercase font-bold text-slate-400">DVR 1</span>
              <button
                v-if="getDvr(store, 1)"
                type="button"
                @click.stop="openChecklistModal(getDvr(store, 1), store)"
                class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 hover:bg-blue-600 hover:text-white"
              >
                + Cek
              </button>
            </div>
            <div v-if="getDvr(store, 1)" class="space-y-1">
              <div class="flex items-center space-x-1.5">
                <span :class="dvrStatusDot(getDvr(store, 1).status)" class="h-2 w-2 rounded-full shrink-0"></span>
                <span class="font-mono font-medium">{{ getDvr(store, 1).ip_address }}</span>
                <span :class="dvrStatusPill(getDvr(store, 1).status)" class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase">
                  {{ getDvr(store, 1).status }}
                </span>
              </div>
              <div v-if="getDvr(store, 1).latest_check" class="space-y-1 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px]">
                <div class="flex items-center space-x-1">
                  <span
                    :class="getDvr(store, 1).latest_check.has_issues
                      ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                      : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'"
                    class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                  >
                    {{ getDvr(store, 1).latest_check.has_issues ? '⚠️ ' + (getDvr(store, 1).latest_check.issues?.join(', ') || 'Ada Temuan') : '✓ Normal (OK)' }}
                  </span>
                </div>
                <p class="text-slate-500 dark:text-slate-400">
                  🕒 {{ getDvr(store, 1).latest_check.formatted_date_time || formatDate(getDvr(store, 1).latest_check.check_timestamp) }}
                </p>
                <p class="text-slate-500 dark:text-slate-400">
                  Kam: {{ getDvr(store, 1).latest_check.camera_working_count }} OK<span v-if="getDvr(store, 1).latest_check.camera_broken_count > 0">, {{ getDvr(store, 1).latest_check.camera_broken_count }} Rusak</span> • HDD: {{ getDvr(store, 1).latest_check.hdd_status }}
                </p>
              </div>
              <span v-else class="text-[10px] text-slate-400 block italic">Belum pernah dicek</span>
            </div>
            <span v-else class="text-slate-400 text-xs">-</span>
          </div>

          <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-[10px] uppercase font-bold text-slate-400">DVR 2</span>
              <button
                v-if="getDvr(store, 2)"
                type="button"
                @click.stop="openChecklistModal(getDvr(store, 2), store)"
                class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 hover:bg-blue-600 hover:text-white"
              >
                + Cek
              </button>
            </div>
            <div v-if="getDvr(store, 2)" class="space-y-1">
              <div class="flex items-center space-x-1.5">
                <span :class="dvrStatusDot(getDvr(store, 2).status)" class="h-2 w-2 rounded-full shrink-0"></span>
                <span class="font-mono font-medium">{{ getDvr(store, 2).ip_address }}</span>
                <span :class="dvrStatusPill(getDvr(store, 2).status)" class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase">
                  {{ getDvr(store, 2).status }}
                </span>
              </div>
              <div v-if="getDvr(store, 2).latest_check" class="space-y-1 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-[10px]">
                <div class="flex items-center space-x-1">
                  <span
                    :class="getDvr(store, 2).latest_check.has_issues
                      ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                      : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'"
                    class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                  >
                    {{ getDvr(store, 2).latest_check.has_issues ? '⚠️ ' + (getDvr(store, 2).latest_check.issues?.join(', ') || 'Ada Temuan') : '✓ Normal (OK)' }}
                  </span>
                </div>
                <p class="text-slate-500 dark:text-slate-400">
                  🕒 {{ getDvr(store, 2).latest_check.formatted_date_time || formatDate(getDvr(store, 2).latest_check.check_timestamp) }}
                </p>
                <p class="text-slate-500 dark:text-slate-400">
                  Kam: {{ getDvr(store, 2).latest_check.camera_working_count }} OK<span v-if="getDvr(store, 2).latest_check.camera_broken_count > 0">, {{ getDvr(store, 2).latest_check.camera_broken_count }} Rusak</span> • HDD: {{ getDvr(store, 2).latest_check.hdd_status }}
                </p>
              </div>
              <span v-else class="text-[10px] text-slate-400 block italic">Belum pernah dicek</span>
            </div>
            <span v-else class="text-slate-400 text-xs">-</span>
          </div>
        </div>

        <!-- Action Button -->
        <div class="grid grid-cols-2 gap-2">
          <button
            v-if="store.dvrs?.length > 0"
            type="button"
            @click="openChecklistModal(store.dvrs[0], store)"
            class="h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center space-x-1 shadow-sm transition-all"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>Catat Checklist</span>
          </button>
          <router-link
            :to="`/stores/${store.id}`"
            :class="store.dvrs?.length > 0 ? 'bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200' : 'col-span-2 bg-blue-600 hover:bg-blue-700 text-white'"
            class="h-11 rounded-xl font-bold text-xs flex items-center justify-center space-x-1 shadow-sm transition-all"
          >
            <span>Detail &amp; Akun</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="pagination.total_records > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500">
      <p>
        Menampilkan data <strong class="text-slate-700 dark:text-slate-300">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</strong>
        s.d <strong class="text-slate-700 dark:text-slate-300">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total_records) }}</strong>
        dari <strong class="text-slate-700 dark:text-slate-300">{{ pagination.total_records }}</strong> toko
      </p>

      <div class="flex items-center space-x-1.5">
        <button
          :disabled="pagination.current_page <= 1"
          @click="goToPage(pagination.current_page - 1)"
          class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 disabled:opacity-40 font-medium hover:bg-slate-50"
        >
          « Sebelumnya
        </button>
        <span class="px-3 py-1.5 font-bold font-mono text-slate-700 dark:text-slate-200">
          Hal. {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          :disabled="pagination.current_page >= pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
          class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 disabled:opacity-40 font-medium hover:bg-slate-50"
        >
          Selanjutnya »
        </button>
      </div>
    </div>

    <!-- Modal OTP Ekspor (BR-NET-002) -->
    <ExportOtpModal
      :is-open="isOtpModalOpen"
      :format="exportFormat"
      @close="isOtpModalOpen = false"
      @verified="onOtpVerified"
    />

    <!-- Modal Impor Data Toko -->
    <StoreImportModal
      :is-open="isImportOpen"
      @close="isImportOpen = false"
      @imported="fetchStores"
    />

    <!-- Modal Tambah Toko Baru (Super Admin) -->
    <div v-if="isAddStoreOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tambah Toko & Inisialisasi DVR</h3>
          <button @click="isAddStoreOpen = false" class="text-slate-400 hover:text-slate-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="createStore" class="py-4 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Toko</label>
              <input v-model="newStore.store_code" type="text" placeholder="T001" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 uppercase" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
              <select v-model="newStore.status" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <option value="Active">Active</option>
                <option value="Renovation">Renovation</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Toko</label>
            <input v-model="newStore.store_name" type="text" placeholder="Toko Grand Central" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Region / Wilayah</label>
              <input v-model="newStore.region" type="text" placeholder="Jabodetabek" required class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">IP Subnet Toko</label>
              <input v-model="newStore.ip_subnet" type="text" placeholder="10.10.1.0/24" class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
            </div>
          </div>

          <!-- Section DVR 1 -->
          <div class="p-3 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/40 space-y-3">
            <span class="block text-xs font-bold text-blue-700 dark:text-blue-300">Konfigurasi DVR 1 (Otomatis Buat 5 Akun)</span>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-1">IP DVR 1</label>
                <input v-model="newStore.dvr1_ip" type="text" placeholder="192.168.25.200" class="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800" />
              </div>
              <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Merk</label>
                <input v-model="newStore.dvr1_brand" type="text" placeholder="Hikvision" class="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800" />
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-slate-800">
            <button type="button" @click="isAddStoreOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:bg-slate-100">Batal</button>
            <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-md">Simpan Toko</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Checklist Lapangan (Quick Cek Langsung dari Direktori) -->
    <ChecklistFormModal
      v-if="selectedDvrForChecklist"
      :is-open="isChecklistModalOpen"
      :dvr-id="selectedDvrForChecklist.id"
      :dvr-label="selectedDvrForChecklist.label"
      :dvr-ip="selectedDvrForChecklist.ip_address"
      :total-channels="selectedDvrForChecklist.total_channels || 8"
      @close="isChecklistModalOpen = false"
      @submitted="onChecklistSubmitted"
    />

    <!-- Toast Notification -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="toastMessage"
        class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-2xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-2xl border border-slate-700/50 dark:border-slate-200 text-xs font-bold flex items-center space-x-2.5 pointer-events-auto"
      >
        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
        <span>{{ toastMessage }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';
import { useAuthStore } from '@/stores/auth';
import { useNetworkStore } from '@/stores/network';
import ExportOtpModal from '@/components/ExportOtpModal.vue';
import StoreImportModal from '@/components/StoreImportModal.vue';
import ChecklistFormModal from '@/components/ChecklistFormModal.vue';

const authStore = useAuthStore();
const networkStore = useNetworkStore();

const stores = ref([]);
const availableRegions = ref([]);
const isLoading = ref(false);

const filters = ref({
  search: '',
  region: 'All',
  status: 'All',
  page: 1,
});

const pagination = ref({
  current_page: 1,
  per_page: 15,
  total_records: 0,
  last_page: 1,
});

const isOtpModalOpen = ref(false);
const exportFormat = ref('xlsx');
const isImportOpen = ref(false);
const isAddStoreOpen = ref(false);
const isExporting = ref(false);
const isDownloadingTemplate = ref(false);

const isChecklistModalOpen = ref(false);
const selectedDvrForChecklist = ref(null);
const toastMessage = ref('');
let toastTimer = null;

const showToast = (msg) => {
  toastMessage.value = msg;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastMessage.value = '';
  }, 4000);
};

const openChecklistModal = (dvr, store) => {
  selectedDvrForChecklist.value = {
    ...dvr,
    label: `${store.store_code} - ${dvr.label || 'DVR ' + dvr.dvr_index}`,
  };
  isChecklistModalOpen.value = true;
};

const onChecklistSubmitted = async () => {
  showToast('✓ Hasil checklist lapangan berhasil dicatat & data toko diperbarui!');
  await fetchStores();
};

const newStore = ref({
  store_code: '',
  store_name: '',
  region: 'Jabodetabek',
  status: 'Active',
  ip_subnet: '',
  create_dvr1: true,
  dvr1_ip: '192.168.25.200',
  dvr1_brand: 'Hikvision',
});

let debounceTimer = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.value.page = 1;
    fetchStores();
  }, 250);
};

const fetchStores = async () => {
  isLoading.value = true;
  try {
    const response = await api.get('/stores', {
      params: {
        search: filters.value.search,
        region: filters.value.region,
        status: filters.value.status,
        page: filters.value.page,
      },
    });
    stores.value = response.data.data;
    pagination.value = response.data.meta;
    if (response.data.filters?.regions) {
      availableRegions.value = response.data.filters.regions;
    }
  } catch (error) {
    console.error('Gagal memuat toko:', error);
  } finally {
    isLoading.value = false;
  }
};

const goToPage = (page) => {
  filters.value.page = page;
  fetchStores();
};

const getDvr = (store, index) => {
  return store.dvrs?.find(d => d.dvr_index === index);
};

const statusBadge = (status) => {
  if (status === 'Active') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400';
  if (status === 'Renovation') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400';
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
};

const dvrStatusDot = (status) => {
  if (status === 'Online') return 'bg-emerald-500';
  if (status === 'Degraded') return 'bg-amber-500';
  return 'bg-red-500';
};

const dvrStatusPill = (status) => {
  if (status === 'Online') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400';
  if (status === 'Degraded') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400';
  return 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400';
};

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }) + ' WIB';
};

const initiateExport = (format) => {
  if (pagination.value.total_records === 0 || stores.value.length === 0) {
    showToast('⚠️ Data toko masih kosong. Tidak ada data yang dapat diekspor.');
    return;
  }
  exportFormat.value = format;
  if (networkStore.isWan) {
    isOtpModalOpen.value = true;
  } else {
    downloadExportFile(format);
  }
};

const onOtpVerified = (otp) => {
  downloadExportFile(exportFormat.value, otp);
};

const downloadExportFile = async (format, otp = null) => {
  if (pagination.value.total_records === 0 || stores.value.length === 0) {
    showToast('⚠️ Data toko masih kosong. Tidak ada data yang dapat diekspor.');
    return;
  }
  isExporting.value = true;
  try {
    const response = await api.get('/stores/export', {
      params: { format, otp },
      responseType: 'blob',
    });

    if (response.data.type === 'application/json') {
      const text = await response.data.text();
      const json = JSON.parse(text);
      showToast(`⚠️ ${json.message || 'Gagal mengekspor data.'}`);
      return;
    }

    const blob = new Blob([response.data], {
      type: format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', `CDAMS_Direktori_Toko_${new Date().toISOString().slice(0, 10)}.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);
    showToast(`✓ Berkas data toko (${format.toUpperCase()}) berhasil diunduh.`);
  } catch (error) {
    let errorMsg = 'Gagal mengunduh file ekspor.';
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const json = JSON.parse(text);
        errorMsg = json.message || errorMsg;
      } catch (_) {}
    } else if (error.response?.data?.message) {
      errorMsg = error.response.data.message;
    }
    showToast(`⚠️ ${errorMsg}`);
  } finally {
    isExporting.value = false;
  }
};

const downloadTemplateFile = async (format = 'xlsx') => {
  isDownloadingTemplate.value = true;
  try {
    const response = await api.get('/stores/template', {
      params: { format },
      responseType: 'blob',
    });
    const blob = new Blob([response.data], {
      type: format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', `cdams_template_impor_toko.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);
    showToast(`✓ Template impor (${format.toUpperCase()}) berhasil diunduh.`);
  } catch (error) {
    showToast('⚠️ Gagal mengunduh berkas template.');
  } finally {
    isDownloadingTemplate.value = false;
  }
};

const openAddStoreModal = () => {
  newStore.value = {
    store_code: '',
    store_name: '',
    region: 'Jabodetabek',
    status: 'Active',
    ip_subnet: '',
    create_dvr1: true,
    dvr1_ip: '192.168.25.200',
    dvr1_brand: 'Hikvision',
  };
  isAddStoreOpen.value = true;
};

const createStore = async () => {
  try {
    await api.post('/stores', newStore.value);
    isAddStoreOpen.value = false;
    fetchStores();
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambahkan toko.');
  }
};

onMounted(() => {
  fetchStores();
});
</script>
