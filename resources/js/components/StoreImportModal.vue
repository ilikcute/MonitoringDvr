<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

    <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 shadow-2xl ring-1 ring-black/10 dark:ring-white/10 transition-all">
      <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center space-x-2">
          <span>Import Data Toko & DVR</span>
        </h3>
        <button @click="close" class="text-slate-400 hover:text-slate-500">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="py-4 space-y-4">
        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl">
          <span>Belum punya format file yang sesuai?</span>
          <a
            href="/api/v1/stores/template?format=xlsx"
            target="_blank"
            class="font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center space-x-1"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>Unduh Template Excel</span>
          </a>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
            Pilih Berkas Spreadsheet (.xlsx, .csv)
          </label>
          <div
            class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
            @click="$refs.fileInput.click()"
          >
            <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
              <span class="font-semibold text-blue-600">Klik untuk memilih</span> atau seret file ke sini
            </p>
            <p class="text-xs text-slate-400">Format didukung: XLSX, CSV (Maks. 10MB)</p>
            <p v-if="selectedFile" class="mt-2 text-xs font-bold text-emerald-600">
              Terpilih: {{ selectedFile.name }} ({{ (selectedFile.size / 1024).toFixed(1) }} KB)
            </p>
          </div>
          <input
            ref="fileInput"
            type="file"
            accept=".xlsx,.csv,.xls"
            class="hidden"
            @change="onFileSelected"
          />
        </div>

        <!-- Result / Errors -->
        <div v-if="result" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-900/40 text-xs space-y-1">
          <p class="font-bold text-emerald-700 dark:text-emerald-300">Impor Berhasil!</p>
          <p class="text-emerald-600 dark:text-emerald-400">{{ result.imported }} toko baru ditambahkan, {{ result.updated }} toko diperbarui.</p>
        </div>

        <div v-if="errorMessage" class="p-3 rounded-xl bg-red-50 dark:bg-red-950/60 text-xs text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </div>
      </div>

      <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button
          type="button"
          class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl"
          @click="close"
        >
          Tutup
        </button>
        <button
          type="button"
          :disabled="!selectedFile || isUploading"
          class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl shadow-md flex items-center space-x-2"
          @click="uploadFile"
        >
          <span v-if="isUploading" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
          <span>{{ isUploading ? 'Mengunggah...' : 'Mulai Impor' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/api/client';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close', 'imported']);

const selectedFile = ref(null);
const isUploading = ref(false);
const result = ref(null);
const errorMessage = ref('');

const onFileSelected = (e) => {
  const files = e.target.files;
  if (files && files.length > 0) {
    selectedFile.value = files[0];
    errorMessage.value = '';
    result.value = null;
  }
};

const uploadFile = async () => {
  if (!selectedFile.value) return;

  isUploading.value = true;
  errorMessage.value = '';
  result.value = null;

  const formData = new FormData();
  formData.append('file', selectedFile.value);

  try {
    const response = await api.post('/stores/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    result.value = response.data.data;
    emit('imported');
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengimpor file. Pastikan struktur kolom valid.';
  } finally {
    isUploading.value = false;
  }
};

const close = () => {
  selectedFile.value = null;
  result.value = null;
  errorMessage.value = '';
  emit('close');
};
</script>
