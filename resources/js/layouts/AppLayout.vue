<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col">
    <!-- Desktop Layout -->
    <div class="flex-1 flex">
      <!-- Sidebar (Desktop >= 1024px) -->
      <aside
        class="hidden lg:flex flex-col w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0 sticky top-0 h-screen transition-all duration-300"
      >
        <!-- Logo & Branding -->
        <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800">
          <div class="flex items-center space-x-3">
            <div class="h-9 w-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <span class="font-extrabold text-base tracking-tight text-slate-900 dark:text-white">CDAMS</span>
              <span class="block text-[10px] text-slate-400 font-semibold uppercase tracking-wider">CCTV & Access System</span>
            </div>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-5 space-y-1.5 overflow-y-auto">
          <router-link
            to="/"
            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
            :class="isActive('/') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Dashboard</span>
          </router-link>

          <router-link
            to="/stores"
            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
            :class="isActive('/stores') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Direktori Toko</span>
          </router-link>

          <router-link
            to="/checks"
            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
            :class="isActive('/checks') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span>Checklist Lapangan</span>
          </router-link>

          <router-link
            v-if="authStore.isSuperAdmin"
            to="/users"
            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
            :class="isActive('/users') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Manajemen Pengguna</span>
          </router-link>

          <router-link
            v-if="authStore.isSuperAdmin"
            to="/audit-logs"
            class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors"
            :class="isActive('/audit-logs') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Audit Trail</span>
          </router-link>
        </nav>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2.5 overflow-hidden">
              <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-200 shrink-0">
                {{ userInitials }}
              </div>
              <div class="truncate">
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ authStore.user?.name }}</p>
                <p class="text-[10px] text-slate-400 capitalize">{{ formatRole(authStore.user?.role) }}</p>
              </div>
            </div>
            <button
              @click="authStore.logout"
              class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-800 transition-colors"
              title="Logout"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0 pb-16 lg:pb-0">
        <!-- Top Header Bar -->
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between">
          <!-- Global Search Trigger (Ctrl+K) -->
          <button
            @click="isSearchOpen = true"
            class="flex items-center space-x-2.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 text-slate-400 hover:border-blue-500 text-xs sm:text-sm w-48 sm:w-72 transition-colors"
          >
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span class="flex-1 text-left truncate">Cari toko atau DVR...</span>
            <kbd class="hidden sm:inline-block px-1.5 py-0.5 rounded text-[10px] font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-400">
              Ctrl K
            </kbd>
          </button>

          <!-- Right Header Controls: Network Mode Badge + Dark Mode Toggle -->
          <div class="flex items-center space-x-3 sm:space-x-4">
            <!-- Network Mode Badge & Simulation Switch -->
            <div class="relative group">
              <button
                type="button"
                @click="networkStore.toggleMode"
                class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all border shadow-sm"
                :class="networkStore.isLan ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-400 dark:border-amber-800'"
                title="Klik untuk beralih simulasi LAN / WAN (Fitur Pengujian BR-NET-001)"
              >
                <span class="h-2 w-2 rounded-full" :class="networkStore.isLan ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                <span>{{ networkStore.currentMode }}</span>
                <span v-if="networkStore.isSimulated" class="text-[9px] opacity-75 font-normal">(Simulasi)</span>
              </button>
            </div>

            <!-- Dark / Light Mode Toggle -->
            <button
              @click="themeStore.toggleTheme"
              class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              title="Toggle Tema"
            >
              <svg v-if="themeStore.isDark" class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <svg v-else class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>
          </div>
        </header>

        <!-- Main View Outlet -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
          <router-view />
        </main>
      </div>
    </div>

    <!-- Mobile Bottom Navigation Bar (< 768px) -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 flex items-center justify-around h-16 px-2">
      <router-link
        to="/"
        class="flex flex-col items-center justify-center w-16 py-1 text-xs font-semibold"
        :class="isActive('/') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 hover:text-slate-600'"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span class="text-[10px] mt-0.5">Dashboard</span>
      </router-link>

      <router-link
        to="/stores"
        class="flex flex-col items-center justify-center w-16 py-1 text-xs font-semibold"
        :class="isActive('/stores') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 hover:text-slate-600'"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span class="text-[10px] mt-0.5">Toko</span>
      </router-link>

      <router-link
        to="/checks"
        class="flex flex-col items-center justify-center w-16 py-1 text-xs font-semibold"
        :class="isActive('/checks') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 hover:text-slate-600'"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
        <span class="text-[10px] mt-0.5">Checklist</span>
      </router-link>

      <button
        @click="isSearchOpen = true"
        class="flex flex-col items-center justify-center w-16 py-1 text-xs font-semibold text-slate-400 hover:text-slate-600"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="text-[10px] mt-0.5">Cari</span>
      </button>

      <button
        @click="authStore.logout"
        class="flex flex-col items-center justify-center w-16 py-1 text-xs font-semibold text-slate-400 hover:text-red-600"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span class="text-[10px] mt-0.5">Keluar</span>
      </button>
    </nav>

    <!-- Global Search Modal (Ctrl+K) -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNetworkStore } from '@/stores/network';
import { useThemeStore } from '@/stores/theme';
import GlobalSearchModal from '@/components/GlobalSearchModal.vue';

const route = useRoute();
const authStore = useAuthStore();
const networkStore = useNetworkStore();
const themeStore = useThemeStore();

const isSearchOpen = ref(false);

const isActive = (path) => {
  if (path === '/') return route.path === '/';
  return route.path.startsWith(path);
};

const userInitials = computed(() => {
  const name = authStore.user?.name || 'U';
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const formatRole = (role) => {
  const map = {
    'superadmin': 'Super Admin EDP',
    'technician': 'Teknisi Lapangan',
    'dept_operator': `Operator ${authStore.departmentCode || 'Dept'}`,
    'management': 'Management Viewer',
  };
  return map[role] || role || 'User';
};

// Keyboard listener untuk Ctrl + K
const handleKeyDown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    isSearchOpen.value = true;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>
