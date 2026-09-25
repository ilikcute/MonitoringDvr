<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

    <div class="mx-auto max-w-2xl transform divide-y divide-slate-200 dark:divide-slate-800 overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-black/10 dark:ring-white/10 transition-all">
      <!-- Search Bar -->
      <div class="relative flex items-center px-4">
        <svg class="pointer-events-none h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
        </svg>
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          class="h-14 w-full border-0 bg-transparent pl-3 pr-12 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 sm:text-base outline-none"
          placeholder="Cari kode toko (contoh: T001) atau nama toko..."
          @input="handleSearch"
          @keydown.esc="close"
        />
        <kbd class="hidden sm:inline-block rounded-md border border-slate-300 dark:border-slate-700 px-2 py-0.5 text-xs text-slate-400 font-mono">
          ESC
        </kbd>
      </div>

      <!-- Results List -->
      <div v-if="isLoading" class="p-8 text-center text-slate-400">
        <div class="inline-block animate-spin h-6 w-6 border-2 border-blue-600 border-t-transparent rounded-full mb-2"></div>
        <p class="text-sm">Mencari direktori toko & DVR...</p>
      </div>

      <div v-else-if="results.length > 0" class="max-h-96 overflow-y-auto p-2 space-y-1">
        <div
          v-for="store in results"
          :key="store.id"
          class="flex items-center justify-between p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer transition-colors group"
          @click="selectStore(store.id)"
        >
          <div class="flex items-center space-x-3">
            <span class="px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300">
              {{ store.store_code }}
            </span>
            <div>
              <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                {{ store.store_name }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Region: {{ store.region }} • {{ store.dvrs_count }} DVR terpasang
              </p>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <span
              :class="store.status === 'Active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
              class="px-2 py-0.5 text-xs rounded-full font-medium"
            >
              {{ store.status }}
            </span>
            <svg class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </div>

      <div v-else-if="query.length >= 2" class="p-8 text-center text-slate-500 dark:text-slate-400">
        <p class="text-sm">Tidak ditemukan toko dengan kata kunci "{{ query }}"</p>
      </div>

      <div v-else class="p-6 text-center text-xs text-slate-400 dark:text-slate-500">
        Ketik minimal 2 karakter untuk memulai pencarian instan (&lt; 1 detik).
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/client';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);
const router = useRouter();

const searchInput = ref(null);
const query = ref('');
const results = ref([]);
const isLoading = ref(false);
let debounceTimeout = null;

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    query.value = '';
    results.value = [];
    nextTick(() => {
      searchInput.value?.focus();
    });
  }
});

const handleSearch = () => {
  clearTimeout(debounceTimeout);
  if (query.value.trim().length < 2) {
    results.value = [];
    return;
  }

  isLoading.value = true;
  debounceTimeout = setTimeout(async () => {
    try {
      const response = await api.get('/stores', {
        params: { search: query.value, per_page: 8 },
      });
      results.value = response.data.data;
    } catch {
      results.value = [];
    } finally {
      isLoading.value = false;
    }
  }, 200);
};

const selectStore = (id) => {
  close();
  router.push(`/stores/${id}`);
};

const close = () => {
  emit('close');
};
</script>
