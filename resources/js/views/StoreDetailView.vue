<template>
  <div v-if="isLoading" class="p-12 text-center text-slate-400">
    <div class="inline-block animate-spin h-8 w-8 border-2 border-blue-600 border-t-transparent rounded-full mb-3"></div>
    <p class="text-sm">Memuat data toko & kredensial DVR...</p>
  </div>

  <div v-else-if="store" class="space-y-6 animate-fadeIn">
    <!-- Back & Breadcrumb -->
    <div class="flex items-center justify-between">
      <router-link
        to="/stores"
        class="inline-flex items-center space-x-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Direktori Toko</span>
      </router-link>

      <div class="flex items-center space-x-2">
        <button
          v-if="authStore.isSuperAdmin"
          @click="openEditStoreModal"
          class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50"
        >
          Edit Toko
        </button>
      </div>
    </div>

    <!-- Store Profile Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 border border-slate-200/80 dark:border-slate-800 shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-3">
            <span class="font-mono text-sm sm:text-base font-extrabold px-3 py-1 rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
              {{ store.store_code }}
            </span>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">
              {{ store.store_name }}
            </h1>
            <span :class="statusBadge(store.status)" class="px-2.5 py-0.5 text-xs rounded-full font-bold uppercase">
              {{ store.status }}
            </span>
          </div>

          <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
            <span><strong>Region:</strong> {{ store.region }}</span>
            <span v-if="store.ip_subnet"><strong>Subnet IP:</strong> {{ store.ip_subnet }}</span>
            <span v-if="store.address"><strong>Alamat:</strong> {{ store.address }}</span>
            <span v-if="store.contact_person"><strong>Kontak:</strong> {{ store.contact_person }} ({{ store.phone || '-' }})</span>
          </div>
        </div>

        <!-- Extra DVR status / capacity override badge -->
        <div v-if="store.allow_extra_dvr" class="px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 text-xs font-bold shrink-0">
          ⭐ Override Kapasitas Aktif (&gt;2 DVR)
        </div>
      </div>
    </div>

    <!-- Segmented DVR Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
      <div class="flex items-center space-x-2">
        <button
          v-for="dvr in store.dvrs"
          :key="dvr.id"
          @click="activeDvrId = dvr.id"
          class="px-4 py-2 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 border"
          :class="activeDvrId === dvr.id
            ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20'
            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-100'"
        >
          <span :class="dvrStatusDot(dvr.status)" class="h-2 w-2 rounded-full"></span>
          <span>DVR {{ dvr.dvr_index }} — {{ dvr.label }}</span>
        </button>

        <!-- Tambah DVR 2 jika baru ada 1 unit (BR-STR-002) -->
        <button
          v-if="(store.dvrs.length < 2 || store.allow_extra_dvr) && (authStore.isSuperAdmin || authStore.isTechnician)"
          @click="isAddDvrOpen = true"
          class="px-3 py-2 rounded-2xl text-xs font-bold text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 border border-dashed border-slate-300 dark:border-slate-700 transition-colors flex items-center space-x-1"
        >
          <span>+ Tambah DVR {{ store.dvrs.length + 1 }}</span>
        </button>
      </div>

      <!-- Tombol Mulai Checklist untuk DVR Aktif -->
      <div v-if="activeDvr">
        <button
          @click="isChecklistModalOpen = true"
          class="px-4 py-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition-all flex items-center space-x-1.5"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <span>Catat Checklist Lapangan</span>
        </button>
      </div>
    </div>

    <!-- Active DVR Details Panel -->
    <div v-if="activeDvr" class="space-y-6">
      <!-- DVR Technical Specification Card -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <span class="text-xs uppercase font-extrabold text-blue-600 tracking-wider">Spesifikasi Teknis DVR {{ activeDvr.dvr_index }}</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">{{ activeDvr.label }}</h3>
          </div>

          <!-- Card Action Buttons -->
          <div class="flex items-center space-x-2">
            <!-- Tombol Edit Detail & Spesifikasi DVR (EDP & SuperAdmin) -->
            <button
              v-if="authStore.isSuperAdmin || authStore.isTechnician"
              @click="openEditDvrModal"
              class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition-all shadow-sm flex items-center space-x-1.5"
            >
              <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              <span>Edit Detail DVR</span>
            </button>

            <!-- Dedicated Ping Test Button for this DVR -->
            <PingTestButton
              :dvr-id="activeDvr.id"
              :ip="activeDvr.ip_address"
              :port="activeDvr.http_port"
              @ping-completed="onPingCompleted"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
          <!-- 1. Serial Number (SN) -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Serial Number (SN)</span>
            <span class="font-bold font-mono text-slate-800 dark:text-white mt-0.5 block truncate" :title="activeDvr.serial_number || 'Belum diatur'">
              {{ activeDvr.serial_number || '-' }}
            </span>
          </div>

          <!-- 2. Merk & Model -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Merk & Model</span>
            <span class="font-bold text-slate-800 dark:text-white mt-0.5 block">{{ activeDvr.brand }} {{ activeDvr.model_series || '' }}</span>
          </div>

          <!-- 3. Alamat IP DVR -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Alamat IP DVR</span>
            <span class="font-bold font-mono text-slate-800 dark:text-white mt-0.5 block">{{ activeDvr.ip_address }}</span>
          </div>

          <!-- 4. Port Jaringan -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Port Jaringan</span>
            <span class="font-mono text-slate-800 dark:text-white mt-0.5 block">HTTP:{{ activeDvr.http_port }} | RTSP:{{ activeDvr.rtsp_port }}</span>
          </div>

          <!-- 5. Channel CCTV -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Channel CCTV</span>
            <span class="font-bold text-slate-800 dark:text-white mt-0.5 block">{{ activeDvr.total_channels }} Channel</span>
          </div>

          <!-- 6. Kapasitas HDD / Retensi -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Kapasitas HDD / Retensi</span>
            <span class="font-bold text-slate-800 dark:text-white mt-0.5 block">
              {{ activeDvr.storage_capacity_tb ? `${activeDvr.storage_capacity_tb} TB` : '-' }} / {{ activeDvr.retention_days ? `${activeDvr.retention_days} Hari` : '-' }}
            </span>
          </div>

          <!-- 7. Status & Terakhir Cek -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
            <span class="text-slate-400 font-semibold block text-[10px] uppercase">Status &amp; Terakhir Cek</span>
            <div class="flex items-center space-x-1.5 mt-1">
              <span :class="dvrStatusDot(activeDvr.status)" class="h-2 w-2 rounded-full"></span>
              <span class="font-bold text-slate-800 dark:text-white">{{ activeDvr.status }}</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1 truncate" :title="activeDvr.last_check_at ? formatDateTime(activeDvr.last_check_at) : 'Belum pernah dicek'">
              {{ activeDvr.last_check_at ? formatDateTime(activeDvr.last_check_at) : 'Belum dicek' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Hasil Inspeksi & Pengecekan Lapangan Terakhir (BR-CHK-001 & BR-CHK-002) -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xs uppercase font-extrabold text-emerald-600 tracking-wider">Hasil Pengecekan &amp; Inspeksi Terakhir</span>
              <span v-if="activeDvr.latest_check" :class="hasCheckIssues(activeDvr.latest_check) ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'" class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase">
                {{ hasCheckIssues(activeDvr.latest_check) ? 'Ada Temuan Masalah' : 'Semua Normal' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
              Pemeriksaan Lapangan — Unit DVR {{ activeDvr.dvr_index }} ({{ activeDvr.label }})
            </h3>
          </div>

          <div v-if="activeDvr.latest_check" class="flex flex-wrap items-center gap-2">
            <div class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold flex items-center space-x-1.5">
              <svg class="h-3.5 w-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span><strong>Waktu Cek:</strong> {{ activeDvr.latest_check.formatted_date_time || formatDateTime(activeDvr.latest_check.check_timestamp) }}</span>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-xs font-semibold flex items-center space-x-1">
              <span>👤 <strong>Teknisi:</strong> {{ activeDvr.latest_check.checker_name || 'Teknisi Lapangan' }}</span>
            </div>
          </div>
        </div>

        <!-- Jika Belum Ada Data Checklist -->
        <div v-if="!activeDvr.latest_check" class="p-6 text-center rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700">
          <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Belum pernah dilakukan checklist inspeksi untuk unit DVR ini.</p>
          <p class="text-[11px] text-slate-400 mt-0.5">Teknisi lapangan dapat mencatat hasil inspeksi fisik, kamera, harddisk, dan jam NTP sekarang.</p>
          <button
            @click="isChecklistModalOpen = true"
            class="mt-3 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-all"
          >
            + Mulai Catat Checklist Pertama
          </button>
        </div>

        <!-- Jika Sudah Ada Data Checklist -->
        <div v-else class="space-y-4">
          <!-- 4 Parameter Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <!-- 1. Ping Jaringan -->
            <div class="p-3.5 rounded-2xl border" :class="activeDvr.latest_check.is_ping_online ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-red-50/60 dark:bg-red-950/20 border-red-200 dark:border-red-800/60'">
              <span class="text-[10px] font-bold uppercase tracking-wider block" :class="activeDvr.latest_check.is_ping_online ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'">
                Status Jaringan
              </span>
              <div class="flex items-center space-x-1.5 mt-1 font-bold" :class="activeDvr.latest_check.is_ping_online ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200'">
                <span>{{ activeDvr.latest_check.is_ping_online ? '✓ Ping Online' : '✕ RTO / Offline' }}</span>
              </div>
              <span class="text-[10px] text-slate-500 mt-1 block">Tipe: {{ activeDvr.latest_check.network_type || 'LAN' }}</span>
            </div>

            <!-- 2. Sinkron Jam NTP -->
            <div class="p-3.5 rounded-2xl border" :class="activeDvr.latest_check.is_time_synced ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/60'">
              <span class="text-[10px] font-bold uppercase tracking-wider block" :class="activeDvr.latest_check.is_time_synced ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                Jam RTC / NTP
              </span>
              <div class="flex items-center space-x-1.5 mt-1 font-bold" :class="activeDvr.latest_check.is_time_synced ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'">
                <span>{{ activeDvr.latest_check.is_time_synced ? '✓ Waktu Sinkron' : '⚠️ Out of Sync' }}</span>
              </div>
              <span class="text-[10px] text-slate-500 mt-1 block">
                {{ activeDvr.latest_check.is_time_synced ? 'Deviasi normal (< 180s)' : `Selisih: ${activeDvr.latest_check.time_difference_seconds} detik` }}
              </span>
            </div>

            <!-- 3. Kondisi HDD & Retensi -->
            <div class="p-3.5 rounded-2xl border" :class="activeDvr.latest_check.hdd_status === 'Normal' ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-red-50/60 dark:bg-red-950/20 border-red-200 dark:border-red-800/60'">
              <span class="text-[10px] font-bold uppercase tracking-wider block" :class="activeDvr.latest_check.hdd_status === 'Normal' ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'">
                Kondisi HDD
              </span>
              <div class="flex items-center space-x-1.5 mt-1 font-bold" :class="activeDvr.latest_check.hdd_status === 'Normal' ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200'">
                <span>HDD: {{ activeDvr.latest_check.hdd_status }}</span>
              </div>
              <span class="text-[10px] text-slate-500 mt-1 block">
                Retensi: {{ activeDvr.latest_check.record_retention_days || activeDvr.retention_days || '-' }} Hari
              </span>
            </div>

            <!-- 4. Kondisi Kamera -->
            <div class="p-3.5 rounded-2xl border" :class="activeDvr.latest_check.camera_broken_count === 0 ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/60'">
              <span class="text-[10px] font-bold uppercase tracking-wider block" :class="activeDvr.latest_check.camera_broken_count === 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                Kamera CCTV
              </span>
              <div class="flex items-center space-x-1 mt-1 font-bold">
                <span class="text-emerald-700 dark:text-emerald-300">{{ activeDvr.latest_check.camera_working_count }} Normal</span>
                <span class="text-slate-400">/</span>
                <span :class="activeDvr.latest_check.camera_broken_count > 0 ? 'text-red-600 dark:text-red-400 font-extrabold' : 'text-slate-500'">{{ activeDvr.latest_check.camera_broken_count }} Rusak</span>
              </div>
              <span class="text-[10px] text-slate-500 mt-1 block">Total Port: {{ activeDvr.total_channels }}</span>
            </div>
          </div>

          <!-- Highlight Temuan Masalah Jika Ada -->
          <div v-if="hasCheckIssues(activeDvr.latest_check)" class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-xs">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
              <span class="text-amber-700 dark:text-amber-300 font-extrabold flex items-center space-x-1">
                <span>⚠️ HASIL TEMUAN:</span>
              </span>
              <div class="flex flex-wrap gap-1 font-semibold">
                <span v-for="(iss, idx) in getCheckIssuesList(activeDvr.latest_check)" :key="idx" class="px-2 py-0.5 rounded-lg bg-white/90 dark:bg-amber-900/60 border border-amber-300 dark:border-amber-800 text-[11px] text-amber-900 dark:text-amber-200">
                  {{ iss }}
                </span>
              </div>
            </div>
          </div>

          <!-- Catatan Teknisi -->
          <div v-if="activeDvr.latest_check.notes" class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 text-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Catatan Teknisi Lapangan:</span>
            <p class="text-slate-700 dark:text-slate-300 italic">"{{ activeDvr.latest_check.notes }}"</p>
          </div>
        </div>
      </div>

      <!-- Mapping 5 Akun Departemen (BR-ACC-001 & BR-ACC-002) -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              Standar 5 Akun Kredensial Departemen
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Isolasi wewenang kredensial per divisi (IC, EDP, SPV, DEV, AUD) dengan enkripsi AES-256.
            </p>
          </div>
          <span class="text-xs font-mono text-slate-400 font-semibold">
            {{ activeDvr.accounts.length }} / 5 Akun Terdaftar
          </span>
        </div>

        <div v-if="activeDvr.accounts.length === 0" class="p-8 text-center text-xs text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
          Akun kredensial tidak ditemukan atau peran Anda tidak memiliki hak akses ke departemen pada DVR ini.
        </div>

        <!-- 5 Credential Cards Vertical List -->
        <div class="space-y-3">
          <CredentialCard
            v-for="acc in activeDvr.accounts"
            :key="acc.id"
            :account="acc"
            :dvr-id="activeDvr.id"
            @updated="fetchStore"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form Checklist Lapangan -->
    <ChecklistFormModal
      v-if="activeDvr"
      :is-open="isChecklistModalOpen"
      :dvr-id="activeDvr.id"
      :dvr-label="activeDvr.label"
      :dvr-ip="activeDvr.ip_address"
      :total-channels="activeDvr.total_channels"
      @close="isChecklistModalOpen = false"
      @submitted="onChecklistSubmitted"
    />

    <!-- Modal Tambah DVR 2 -->
    <div v-if="isAddDvrOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
          Tambah DVR Unit Ke-{{ store.dvrs.length + 1 }}
        </h3>
        <form @submit.prevent="createDvr" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold mb-1">Label Perangkat</label>
            <input v-model="newDvr.label" type="text" placeholder="DVR 2 - Area Gudang & Loading" required class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
          </div>
          <div>
            <label class="block font-semibold mb-1">IP Address DVR</label>
            <input v-model="newDvr.ip_address" type="text" placeholder="192.168.25.201" required class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block font-semibold mb-1">Port HTTP</label>
              <input v-model.number="newDvr.http_port" type="number" class="w-full p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
            </div>
            <div>
              <label class="block font-semibold mb-1">Port RTSP</label>
              <input v-model.number="newDvr.rtsp_port" type="number" class="w-full p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800" />
            </div>
          </div>
          <div class="flex justify-end space-x-2 pt-3">
            <button type="button" @click="isAddDvrOpen = false" class="px-4 py-2 font-semibold text-slate-600">Batal</button>
            <button type="submit" class="px-5 py-2 font-bold text-white bg-blue-600 rounded-xl">Simpan DVR</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Spesifikasi & Detail DVR -->
    <div v-if="isEditDvrOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <span class="text-xs uppercase font-extrabold text-blue-600 tracking-wider">Konfigurasi Hardware</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
              Edit Detail DVR {{ activeDvr?.dvr_index }}
            </h3>
          </div>
          <button @click="isEditDvrOpen = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="updateDvr" class="space-y-4 text-xs">
          <!-- Row 1: Label & Serial Number -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Label Perangkat / Area <span class="text-red-500">*</span></label>
              <input
                v-model="editDvrForm.label"
                type="text"
                required
                placeholder="misal: DVR 1 - Area Toko & Kasir"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Serial Number (SN)</label>
              <input
                v-model="editDvrForm.serial_number"
                type="text"
                placeholder="misal: DS-7208HQHI-SN998822"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Row 2: Merk & Model Series -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Merk / Brand</label>
              <input
                v-model="editDvrForm.brand"
                type="text"
                placeholder="misal: Hikvision, Dahua, Uniview"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Model / Seri</label>
              <input
                v-model="editDvrForm.model_series"
                type="text"
                placeholder="misal: DS-7208HQHI-K1/E"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Row 3: IP Address & Ports -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">IP Address <span class="text-red-500">*</span></label>
              <input
                v-model="editDvrForm.ip_address"
                type="text"
                required
                placeholder="192.168.25.200"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Port HTTP</label>
              <input
                v-model.number="editDvrForm.http_port"
                type="number"
                min="1"
                max="65535"
                placeholder="80"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Port RTSP</label>
              <input
                v-model.number="editDvrForm.rtsp_port"
                type="number"
                min="1"
                max="65535"
                placeholder="554"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Row 4: Channels, Storage & Retention -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Total Channel</label>
              <input
                v-model.number="editDvrForm.total_channels"
                type="number"
                min="1"
                max="128"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Kapasitas HDD (TB)</label>
              <input
                v-model.number="editDvrForm.storage_capacity_tb"
                type="number"
                step="0.5"
                min="0.5"
                placeholder="2"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Target Retensi (Hari)</label>
              <input
                v-model.number="editDvrForm.retention_days"
                type="number"
                min="1"
                max="365"
                placeholder="30"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Row 5: Firmware & Status -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Versi Firmware</label>
              <input
                v-model="editDvrForm.firmware_version"
                type="text"
                placeholder="misal: V4.25.001 build 200508"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Status Operasional</label>
              <select
                v-model="editDvrForm.status"
                class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="Online">Online</option>
                <option value="Offline">Offline</option>
                <option value="Degraded">Degraded</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Decommissioned">Decommissioned</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="isEditDvrOpen = false"
              class="px-4 py-2 font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isUpdatingDvr"
              class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center space-x-1.5"
            >
              <span v-if="isUpdatingDvr" class="inline-block animate-spin h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full mr-1"></span>
              <span>Simpan Perubahan DVR</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Profil Toko -->
    <div v-if="isEditStoreOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <span class="text-xs uppercase font-extrabold text-blue-600 tracking-wider">Profil Toko</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
              Edit Data {{ store?.store_code }}
            </h3>
          </div>
          <button @click="isEditStoreOpen = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="updateStore" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Nama Toko <span class="text-red-500">*</span></label>
            <input v-model="editStoreForm.store_name" type="text" required class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Region <span class="text-red-500">*</span></label>
              <input v-model="editStoreForm.region" type="text" required class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Status Operasional</label>
              <select v-model="editStoreForm.status" class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="Active">Active</option>
                <option value="Renovation">Renovation</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Subnet IP Toko</label>
            <input v-model="editStoreForm.ip_subnet" type="text" placeholder="misal: 192.168.25.0/24" class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Alamat</label>
            <textarea v-model="editStoreForm.address" rows="2" class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Kontak Person (PIC)</label>
              <input v-model="editStoreForm.contact_person" type="text" class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Nomor Telepon Toko</label>
              <input v-model="editStoreForm.phone" type="text" class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div class="pt-1 flex items-center space-x-2">
            <input id="allow_extra" v-model="editStoreForm.allow_extra_dvr" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
            <label for="allow_extra" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
              Izinkan lebih dari 2 unit DVR (Override Kapasitas)
            </label>
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="isEditStoreOpen = false" class="px-4 py-2 font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">Batal</button>
            <button type="submit" :disabled="isUpdatingStore" class="px-5 py-2 font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center space-x-1.5">
              <span v-if="isUpdatingStore" class="inline-block animate-spin h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full mr-1"></span>
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>
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
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/api/client';
import { useAuthStore } from '@/stores/auth';
import PingTestButton from '@/components/PingTestButton.vue';
import CredentialCard from '@/components/CredentialCard.vue';
import ChecklistFormModal from '@/components/ChecklistFormModal.vue';

const route = useRoute();
const authStore = useAuthStore();

const store = ref(null);
const isLoading = ref(true);
const activeDvrId = ref(null);
const isChecklistModalOpen = ref(false);
const isAddDvrOpen = ref(false);
const isEditDvrOpen = ref(false);
const isUpdatingDvr = ref(false);
const isEditStoreOpen = ref(false);
const isUpdatingStore = ref(false);
const toastMessage = ref('');
let toastTimer = null;

const newDvr = ref({
  label: '',
  serial_number: '',
  brand: 'Hikvision',
  model_series: '',
  ip_address: '192.168.25.201',
  http_port: 80,
  rtsp_port: 554,
});

const editDvrForm = ref({
  label: '',
  serial_number: '',
  brand: '',
  model_series: '',
  ip_address: '',
  http_port: 80,
  rtsp_port: 554,
  server_port: 8000,
  total_channels: 8,
  storage_capacity_tb: null,
  retention_days: null,
  firmware_version: '',
  status: 'Online',
});

const editStoreForm = ref({
  store_name: '',
  region: '',
  address: '',
  ip_subnet: '',
  contact_person: '',
  phone: '',
  status: 'Active',
  allow_extra_dvr: false,
});

const openEditDvrModal = () => {
  if (!activeDvr.value) return;
  editDvrForm.value = {
    label: activeDvr.value.label || '',
    serial_number: activeDvr.value.serial_number || '',
    brand: activeDvr.value.brand || '',
    model_series: activeDvr.value.model_series || '',
    ip_address: activeDvr.value.ip_address || '',
    http_port: activeDvr.value.http_port ?? 80,
    rtsp_port: activeDvr.value.rtsp_port ?? 554,
    server_port: activeDvr.value.server_port ?? 8000,
    total_channels: activeDvr.value.total_channels ?? 8,
    storage_capacity_tb: activeDvr.value.storage_capacity_tb ?? null,
    retention_days: activeDvr.value.retention_days ?? null,
    firmware_version: activeDvr.value.firmware_version || '',
    status: activeDvr.value.status || 'Offline',
  };
  isEditDvrOpen.value = true;
};

const updateDvr = async () => {
  if (!activeDvr.value) return;
  isUpdatingDvr.value = true;
  try {
    await api.put(`/dvrs/${activeDvr.value.id}`, editDvrForm.value);
    isEditDvrOpen.value = false;
    showToast('✓ Spesifikasi & Serial Number DVR berhasil diperbarui!');
    await fetchStore();
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal memperbarui detail DVR.');
  } finally {
    isUpdatingDvr.value = false;
  }
};

const openEditStoreModal = () => {
  if (!store.value) return;
  editStoreForm.value = {
    store_name: store.value.store_name,
    region: store.value.region,
    address: store.value.address || '',
    ip_subnet: store.value.ip_subnet || '',
    contact_person: store.value.contact_person || '',
    phone: store.value.phone || '',
    status: store.value.status || 'Active',
    allow_extra_dvr: !!store.value.allow_extra_dvr,
  };
  isEditStoreOpen.value = true;
};

const updateStore = async () => {
  if (!store.value) return;
  isUpdatingStore.value = true;
  try {
    await api.put(`/stores/${store.value.id}`, editStoreForm.value);
    isEditStoreOpen.value = false;
    showToast('✓ Profil toko berhasil diperbarui!');
    await fetchStore();
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal memperbarui data toko.');
  } finally {
    isUpdatingStore.value = false;
  }
};

const activeDvr = computed(() => {
  if (!store.value?.dvrs) return null;
  return store.value.dvrs.find(d => d.id === activeDvrId.value) || store.value.dvrs[0];
});

const showToast = (msg) => {
  toastMessage.value = msg;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastMessage.value = '';
  }, 4000);
};

const formatDateTime = (isoString) => {
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

const hasCheckIssues = (check) => {
  if (!check) return false;
  return !check.is_ping_online ||
    !check.is_time_synced ||
    check.hdd_status !== 'Normal' ||
    check.camera_broken_count > 0;
};

const getCheckIssuesList = (check) => {
  if (!check) return [];
  const issues = [];
  if (!check.is_ping_online) issues.push('Ping RTO / Jaringan Offline');
  if (check.camera_broken_count > 0) issues.push(`${check.camera_broken_count} Kamera Rusak/Mati`);
  if (check.hdd_status !== 'Normal') issues.push(`Kondisi HDD ${check.hdd_status}`);
  if (!check.is_time_synced) issues.push(`Waktu Deviasi ${check.time_difference_seconds || '>180'}s (Out of Sync)`);
  return issues;
};

const fetchStore = async () => {
  try {
    const response = await api.get(`/stores/${route.params.id}`);
    store.value = response.data.data;
    if (!activeDvrId.value && store.value.dvrs?.length > 0) {
      activeDvrId.value = store.value.dvrs[0].id;
    }
  } catch (error) {
    console.error('Gagal memuat detail toko:', error);
  } finally {
    isLoading.value = false;
  }
};

const onPingCompleted = (result) => {
  if (activeDvr.value) {
    activeDvr.value.status = result.status;
    showToast(`✓ Hasil ping ke ${activeDvr.value.ip_address}: ${result.status} (${result.response_time_ms ?? '-'}ms)`);
  }
};

const onChecklistSubmitted = () => {
  fetchStore();
  showToast('✓ Hasil checklist lapangan berhasil dicatat & data toko diperbarui!');
};

const createDvr = async () => {
  try {
    await api.post(`/stores/${store.value.id}/dvrs`, newDvr.value);
    isAddDvrOpen.value = false;
    showToast('✓ Unit DVR baru berhasil ditambahkan beserta 5 slot kredensial!');
    fetchStore();
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menambahkan DVR.');
  }
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

onMounted(() => {
  fetchStore();
});
</script>
