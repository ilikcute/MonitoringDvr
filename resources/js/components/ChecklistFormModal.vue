<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

    <div class="relative w-full max-w-lg transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-2xl ring-1 ring-black/10 dark:ring-white/10 transition-all">
      <!-- Header -->
      <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
            <span>Checklist Inspeksi Teknisi</span>
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {{ dvrLabel }} • IP: {{ dvrIp }}
          </p>
        </div>
        <button @click="close" class="text-slate-400 hover:text-slate-500 p-2 rounded-xl">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Form Content with 48x48px Touch Targets -->
      <form @submit.prevent="submitCheck" class="py-5 space-y-6">
        <!-- 1. Ping Online Toggle -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
            Status Ping Jaringan
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              class="h-12 rounded-2xl font-bold text-sm transition-all flex items-center justify-center space-x-2 border"
              :class="form.is_ping_online ? 'bg-emerald-600 text-white border-emerald-600 shadow-md ring-2 ring-emerald-500/20' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
              @click="form.is_ping_online = true"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>Ping Online</span>
            </button>
            <button
              type="button"
              class="h-12 rounded-2xl font-bold text-sm transition-all flex items-center justify-center space-x-2 border"
              :class="!form.is_ping_online ? 'bg-red-600 text-white border-red-600 shadow-md ring-2 ring-red-500/20' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
              @click="form.is_ping_online = false"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span>RTO / Offline</span>
            </button>
          </div>
        </div>

        <!-- 2. Sinkronisasi Jam NTP (BR-CHK-002) -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Sinkronisasi Waktu DVR & NTP
            </label>
            <span v-if="form.time_difference_seconds > 180" class="text-[11px] font-bold text-amber-600">
              ⚠️ Selisih &gt; 180s (Out of Sync)
            </span>
          </div>
          <div class="grid grid-cols-2 gap-3 mb-2">
            <button
              type="button"
              class="h-12 rounded-2xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center space-x-1.5 border"
              :class="form.is_time_synced ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
              @click="setSync(true)"
            >
              <span>Jam Sinkron</span>
            </button>
            <button
              type="button"
              class="h-12 rounded-2xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center space-x-1.5 border"
              :class="!form.is_time_synced ? 'bg-amber-600 text-white border-amber-600 shadow-md' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
              @click="setSync(false)"
            >
              <span>Waktu Berbeda</span>
            </button>
          </div>
          <div v-if="!form.is_time_synced" class="mt-2">
            <label class="block text-[11px] text-slate-500 dark:text-slate-400 mb-1">Selisih Waktu (Detik):</label>
            <input
              v-model.number="form.time_difference_seconds"
              type="number"
              min="0"
              class="w-full h-11 px-3 rounded-xl border border-amber-300 dark:border-amber-700/60 bg-amber-50/50 dark:bg-amber-950/30 text-slate-900 dark:text-white font-mono text-sm focus:outline-none"
              placeholder="Contoh: 300"
            />
          </div>
        </div>

        <!-- 3. Status Harddisk (HDD) & Retensi Rekaman -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
              Kondisi HDD
            </label>
            <select
              v-model="form.hdd_status"
              class="w-full h-12 px-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
              <option value="Normal">Normal (Recording)</option>
              <option value="Error">Error / Rusak</option>
              <option value="Unformatted">Unformatted</option>
              <option value="Full">Full (Tidak Timpa)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
              Retensi Rekaman (Hari)
            </label>
            <input
              v-model.number="form.record_retention_days"
              type="number"
              min="0"
              max="365"
              class="w-full h-12 px-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
              placeholder="Contoh: 30"
            />
          </div>
        </div>

        <!-- 4. Jumlah Kamera: Bekerja vs Rusak (Touch Counter) -->
        <div class="grid grid-cols-2 gap-4">
          <!-- Kamera Normal -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
            <span class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-2 uppercase">Kamera Normal</span>
            <div class="flex items-center justify-center space-x-3">
              <button
                type="button"
                class="h-10 w-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm border border-slate-200 dark:border-slate-600 text-base font-bold text-slate-700 dark:text-slate-200 active:scale-95"
                @click="form.camera_working_count = Math.max(0, form.camera_working_count - 1)"
              >
                -
              </button>
              <span class="font-mono text-xl font-extrabold text-slate-800 dark:text-white w-8">
                {{ form.camera_working_count }}
              </span>
              <button
                type="button"
                class="h-10 w-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm border border-slate-200 dark:border-slate-600 text-base font-bold text-slate-700 dark:text-slate-200 active:scale-95"
                @click="form.camera_working_count++"
              >
                +
              </button>
            </div>
          </div>

          <!-- Kamera Rusak -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
            <span class="block text-xs font-bold text-red-600 dark:text-red-400 mb-2 uppercase">Kamera Rusak</span>
            <div class="flex items-center justify-center space-x-3">
              <button
                type="button"
                class="h-10 w-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm border border-slate-200 dark:border-slate-600 text-base font-bold text-slate-700 dark:text-slate-200 active:scale-95"
                @click="form.camera_broken_count = Math.max(0, form.camera_broken_count - 1)"
              >
                -
              </button>
              <span class="font-mono text-xl font-extrabold text-slate-800 dark:text-white w-8">
                {{ form.camera_broken_count }}
              </span>
              <button
                type="button"
                class="h-10 w-10 rounded-xl bg-white dark:bg-slate-700 shadow-sm border border-slate-200 dark:border-slate-600 text-base font-bold text-slate-700 dark:text-slate-200 active:scale-95"
                @click="form.camera_broken_count++"
              >
                +
              </button>
            </div>
          </div>
        </div>

        <!-- 5. Catatan Lapangan -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
            Catatan Teknisi Lapangan
          </label>
          <textarea
            v-model="form.notes"
            rows="2"
            class="w-full p-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
            placeholder="Kondisi debu dibersihkan, konektor BNC diperiksa, dll..."
          ></textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full h-14 rounded-2xl font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.99] shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center space-x-2"
          >
            <span v-if="isSubmitting" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
            <span class="text-base tracking-wide">{{ isSubmitting ? 'Menyimpan Hasil Cek...' : 'SUBMIT LAPORAN CHECKLIST' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '@/api/client';

const props = defineProps({
  isOpen: Boolean,
  dvrId: { type: [Number, String], required: true },
  dvrLabel: { type: String, default: 'DVR' },
  dvrIp: { type: String, default: '192.168.25.200' },
  totalChannels: { type: Number, default: 8 },
});

const emit = defineEmits(['close', 'submitted']);

const isSubmitting = ref(false);
const form = ref({
  is_ping_online: true,
  is_time_synced: true,
  time_difference_seconds: 0,
  hdd_status: 'Normal',
  record_retention_days: 30,
  camera_working_count: props.totalChannels || 8,
  camera_broken_count: 0,
  notes: '',
});

watch(() => props.isOpen, (open) => {
  if (open) {
    form.value = {
      is_ping_online: true,
      is_time_synced: true,
      time_difference_seconds: 0,
      hdd_status: 'Normal',
      record_retention_days: 30,
      camera_working_count: props.totalChannels || 8,
      camera_broken_count: 0,
      notes: '',
    };
  }
});

const setSync = (synced) => {
  form.value.is_time_synced = synced;
  if (synced) {
    form.value.time_difference_seconds = 0;
  } else if (form.value.time_difference_seconds === 0) {
    form.value.time_difference_seconds = 300;
  }
};

const submitCheck = async () => {
  isSubmitting.value = true;
  try {
    const response = await api.post(`/dvrs/${props.dvrId}/checks`, form.value);
    emit('submitted', response.data.data);
    close();
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menyimpan checklist inspeksi.');
  } finally {
    isSubmitting.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
