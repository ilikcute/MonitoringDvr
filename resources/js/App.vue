<template>
  <div class="h-full">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';

const authStore = useAuthStore();
const themeStore = useThemeStore();

onMounted(() => {
  themeStore.applyTheme();
  if (authStore.token) {
    authStore.fetchUser();
  }
});
</script>
