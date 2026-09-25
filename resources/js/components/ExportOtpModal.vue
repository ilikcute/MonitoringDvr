<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

    <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl ring-1 ring-black/10 dark:ring-white/10 transition-all">
      <div class="flex items-center space-x-3 text-amber-600 dark:text-amber-400 mb-4">
        <div class="p-2.5 rounded-xl bg-amber-100 dark:bg-amber-950/60">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Verifikasi OTP Ekspor WAN</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Aturan Keamanan Jaringan BR-NET-002</p>
        </div>
      </div>

      <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
        Permintaan ekspor data massal berasal dari jaringan <strong class="text-amber-600">WAN (Toko/VPN)</strong>. Masukkan 6-digit OTP otorisasi untuk melanjutkan pengunduhan.
      </p>

      <!-- Banner Simulasi Kode OTP untuk Pengujian -->
      <div v-if="generatedOtp" class="mb-5 p-3 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-900/50">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Simulasi OTP Diterima:</span>
          <button @click="fillOtp" class="text-xs font-bold text-blue-600 hover:underline">Gunakan Kode Ini</button>
        </div>
        <div class="mt-1 text-center font-mono text-2xl font-extrabold tracking-widest text-blue-800 dark:text-blue-200">
          {{ generatedOtp }}
        </div>
      </div>

      <form @submit.prevent="submitOtp" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Kode OTP (6 Digit)
          </label>
          <input
            v-model="otpInput"
            type="text"
            maxlength="6"
            class="w-full text-center tracking-widest font-mono text-xl py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            placeholder="000000"
            required
            autofocus
          />
        </div>

        <div v-if="errorMessage" class="text-xs text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </div>

        <div class="flex items-center justify-end space-x-3 pt-2">
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
            @click="close"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="otpInput.length < 6 || isSubmitting"
            class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl shadow-md transition-all flex items-center space-x-2"
          >
            <span>Verifikasi & Unduh</span>
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
  format: { type: String, default: 'xlsx' },
});

const emit = defineEmits(['close', 'verified']);

const otpInput = ref('');
const generatedOtp = ref('');
const errorMessage = ref('');
const isSubmitting = ref(false);

watch(() => props.isOpen, async (open) => {
  if (open) {
    otpInput.value = '';
    errorMessage.value = '';
    generatedOtp.value = '';
    try {
      const response = await api.get('/stores/export-otp');
      generatedOtp.value = response.data.data.otp_code;
    } catch {
      errorMessage.value = 'Gagal menghasilkan kode OTP.';
    }
  }
});

const fillOtp = () => {
  otpInput.value = generatedOtp.value;
};

const submitOtp = () => {
  if (otpInput.value.length === 6) {
    emit('verified', otpInput.value);
    close();
  }
};

const close = () => {
  emit('close');
};
</script>
