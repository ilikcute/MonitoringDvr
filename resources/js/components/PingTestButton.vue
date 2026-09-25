<template>
  <div class="inline-flex items-center space-x-2">
    <button
      type="button"
      :disabled="isPinging"
      class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold shadow-sm transition-all duration-150 disabled:opacity-50"
      :class="buttonClass"
      @click="runPingTest"
    >
      <span v-if="isPinging" class="animate-spin h-3.5 w-3.5 border-2 border-current border-t-transparent rounded-full"></span>
      <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
      </svg>
      <span>{{ isPinging ? 'Pinging...' : buttonLabel }}</span>
    </button>

    <!-- Hasil Ping Status Badge -->
    <div v-if="lastResult" class="flex items-center space-x-1.5 animate-fadeIn">
      <span
        :class="lastResult.is_online ? 'bg-emerald-500' : 'bg-red-500'"
        class="h-2.5 w-2.5 rounded-full inline-block animate-pulse"
      ></span>
      <span
        :class="lastResult.is_online ? 'text-emerald-700 dark:text-emerald-400 font-bold' : 'text-red-600 dark:text-red-400 font-bold'"
        class="text-xs"
      >
        {{ lastResult.status }} {{ lastResult.latency_ms ? `(${lastResult.latency_ms}ms)` : '' }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import api from '@/api/client';

const props = defineProps({
  dvrId: { type: [Number, String], default: null },
  ip: { type: String, default: '192.168.25.200' },
  port: { type: Number, default: 80 },
  label: { type: String, default: null },
});

const emit = defineEmits(['ping-completed']);

const isPinging = ref(false);
const lastResult = ref(null);

const buttonLabel = computed(() => {
  if (props.label) return props.label;
  return `Test Ping (${props.ip || '192.168.25.200'})`;
});

const buttonClass = computed(() => {
  if (!lastResult.value) {
    return 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-900';
  }
  if (lastResult.value.is_online) {
    return 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
  }
  return 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-800';
});

const runPingTest = async () => {
  isPinging.value = true;
  lastResult.value = null;

  try {
    const endpoint = props.dvrId ? `/dvrs/${props.dvrId}/ping-test` : '/dvrs/ping-test';
    const payload = {
      ip_address: props.ip || '192.168.25.200',
      port: props.port || 80,
    };

    const response = await api.post(endpoint, payload);
    lastResult.value = response.data.data;
    emit('ping-completed', response.data.data);
  } catch (error) {
    lastResult.value = {
      is_online: false,
      status: 'Offline',
      latency_ms: null,
      message: 'Gagal mengeksekusi ping test dari server.',
    };
    emit('ping-completed', lastResult.value);
  } finally {
    isPinging.value = false;
  }
};
</script>
