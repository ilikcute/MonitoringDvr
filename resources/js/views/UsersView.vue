<template>
  <div class="space-y-6 animate-fadeIn">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
          Manajemen Pengguna &amp; RBAC
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Kontrol akses berbasis peran (Super Admin, Teknisi, Operator Divisi, Management) &amp; isolasi wewenang.
        </p>
      </div>

      <button
        @click="openAddModal"
        class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-500/20 transition-all shrink-0"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
        <span>+ Tambah Pengguna</span>
      </button>
    </div>

    <!-- Quick Filters -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
      <!-- Search Input -->
      <div class="relative w-full md:w-80">
        <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="filters.search"
          type="text"
          class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Cari nama, email, atau telepon..."
          @input="debounceSearch"
        />
      </div>

      <div class="flex items-center space-x-3 w-full md:w-auto">
        <!-- Filter Role -->
        <select
          v-model="filters.role"
          @change="fetchUsers"
          class="w-full md:w-44 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none"
        >
          <option value="All">Semua Peran (Role)</option>
          <option value="superadmin">Super Admin EDP</option>
          <option value="technician">Teknisi Lapangan</option>
          <option value="dept_operator">Operator Departemen</option>
          <option value="management">Management Viewer</option>
        </select>

        <!-- Filter Departemen -->
        <select
          v-model="filters.department_id"
          @change="fetchUsers"
          class="w-full md:w-44 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none"
        >
          <option value="All">Semua Departemen</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.code }} — {{ dept.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="p-12 text-center text-slate-400">
      <div class="inline-block animate-spin h-8 w-8 border-2 border-blue-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-sm">Memuat data pengguna...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="users.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-12 text-center border border-slate-200 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Tidak Ada Pengguna Ditemukan</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter peran.</p>
    </div>

    <!-- Users Table (Desktop & Tablet) -->
    <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-x-auto shadow-sm">
      <table class="w-full min-w-[850px] text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50/60 dark:bg-slate-800/40">
            <th class="py-3.5 px-5 min-w-[200px]">Nama &amp; Email</th>
            <th class="py-3.5 px-4 whitespace-nowrap">Peran (Role)</th>
            <th class="py-3.5 px-4 whitespace-nowrap">Departemen / Divisi</th>
            <th class="py-3.5 px-4 whitespace-nowrap">Nomor Telepon</th>
            <th class="py-3.5 px-4 whitespace-nowrap">Status</th>
            <th class="py-3.5 px-6 text-right whitespace-nowrap min-w-[130px]">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
          <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
            <!-- Nama & Email -->
            <td class="py-3.5 px-5">
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 font-bold flex items-center justify-center shrink-0">
                  {{ user.name[0]?.toUpperCase() }}
                </div>
                <div>
                  <span class="font-bold text-slate-900 dark:text-white block">{{ user.name }}</span>
                  <span class="text-[11px] font-mono text-slate-400">{{ user.email }}</span>
                </div>
              </div>
            </td>

            <!-- Role Badge -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span :class="roleBadge(user.role)" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                {{ formatRole(user.role) }}
              </span>
            </td>

            <!-- Department -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span v-if="user.department" class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                [{{ user.department.code }}] {{ user.department.name }}
              </span>
              <span v-else class="text-slate-400">-</span>
            </td>

            <!-- Telepon -->
            <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
              {{ user.phone || '-' }}
            </td>

            <!-- Status Aktif / Nonaktif -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span
                :class="user.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400'"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
              >
                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>

            <!-- Aksi (Edit & Hapus) -->
            <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-2">
              <button
                @click="openEditModal(user)"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-slate-800 transition-colors"
              >
                Edit
              </button>
              <button
                v-if="user.id !== authStore.user?.id"
                @click="confirmDelete(user)"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-slate-800 transition-colors"
              >
                Hapus
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total_records > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-500">
      <p>
        Menampilkan data <strong class="text-slate-700 dark:text-slate-300">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</strong>
        s.d <strong class="text-slate-700 dark:text-slate-300">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total_records) }}</strong>
        dari <strong class="text-slate-700 dark:text-slate-300">{{ pagination.total_records }}</strong> pengguna
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

    <!-- Modal Form Tambah / Edit Pengguna -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ isEditing ? 'Edit Pengguna & Hak Akses' : 'Tambah Pengguna Baru' }}
          </h3>
          <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-500">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveUser" class="py-4 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Contoh: Budi Santoso"
              required
              class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="nama@cdams.local"
              required
              class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ isEditing ? 'Kata Sandi Baru (Kosongkan jika tidak diubah)' : 'Kata Sandi' }}
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!isEditing"
              placeholder="Minimal 6 karakter..."
              class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Peran (Role) -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Peran Pengguna (RBAC)</label>
              <select
                v-model="form.role"
                required
                class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="superadmin">Super Admin EDP (Akses Penuh)</option>
                <option value="technician">Teknisi Lapangan (Checklist &amp; Ping)</option>
                <option value="dept_operator">Operator Departemen (Terisolasi)</option>
                <option value="management">Management Viewer (Read-only)</option>
              </select>
            </div>

            <!-- Departemen (Wajib jika dept_operator) -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Departemen
                <span v-if="form.role === 'dept_operator'" class="text-red-500 font-bold">*</span>
              </label>
              <select
                v-model="form.department_id"
                :required="form.role === 'dept_operator'"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="null">-- Tidak Terikat Departemen --</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.code }} — {{ dept.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Telepon</label>
              <input
                v-model="form.phone"
                type="text"
                placeholder="081234567890"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Akun</label>
              <select
                v-model="form.is_active"
                class="w-full text-xs p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="true">Aktif (Dapat Login)</option>
                <option :value="false">Nonaktif (Diblokir)</option>
              </select>
            </div>
          </div>

          <div v-if="errorMessage" class="p-3 rounded-xl bg-red-50 text-xs text-red-600">
            {{ errorMessage }}
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
            <button type="submit" :disabled="isSaving" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md">
              {{ isSaving ? 'Menyimpan...' : (isEditing ? 'Perbarui Pengguna' : 'Simpan Pengguna') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const users = ref([]);
const departments = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);
const errorMessage = ref('');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const filters = ref({
  search: '',
  role: 'All',
  department_id: 'All',
  page: 1,
});

const pagination = ref({
  current_page: 1,
  per_page: 15,
  total_records: 0,
  last_page: 1,
});

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'dept_operator',
  department_id: null,
  phone: '',
  is_active: true,
});

let debounceTimer = null;
const debounceSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.value.page = 1;
    fetchUsers();
  }, 250);
};

const fetchUsers = async () => {
  isLoading.value = true;
  try {
    const response = await api.get('/users', {
      params: {
        search: filters.value.search,
        role: filters.value.role,
        department_id: filters.value.department_id,
        page: filters.value.page,
      },
    });
    users.value = response.data.data;
    pagination.value = response.data.meta;
  } catch (error) {
    console.error('Gagal memuat pengguna:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchDepartments = async () => {
  try {
    const response = await api.get('/departments');
    departments.value = response.data.data;
  } catch (error) {
    console.error('Gagal memuat master departemen:', error);
  }
};

const goToPage = (page) => {
  filters.value.page = page;
  fetchUsers();
};

const openAddModal = () => {
  isEditing.value = false;
  editingUserId.value = null;
  errorMessage.value = '';
  form.value = {
    name: '',
    email: '',
    password: '',
    role: 'dept_operator',
    department_id: departments.value[0]?.id || null,
    phone: '',
    is_active: true,
  };
  isModalOpen.value = true;
};

const openEditModal = (user) => {
  isEditing.value = true;
  editingUserId.value = user.id;
  errorMessage.value = '';
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    department_id: user.department_id,
    phone: user.phone || '',
    is_active: user.is_active,
  };
  isModalOpen.value = true;
};

const saveUser = async () => {
  isSaving.value = true;
  errorMessage.value = '';

  try {
    if (isEditing.value) {
      await api.put(`/users/${editingUserId.value}`, form.value);
    } else {
      await api.post('/users', form.value);
    }
    isModalOpen.value = false;
    fetchUsers();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal menyimpan data pengguna.';
  } finally {
    isSaving.value = false;
  }
};

const confirmDelete = async (user) => {
  if (confirm(`Apakah Anda yakin ingin menghapus akun ${user.name} (${user.email})?`)) {
    try {
      await api.delete(`/users/${user.id}`);
      fetchUsers();
    } catch (error) {
      alert(error.response?.data?.message || 'Gagal menghapus pengguna.');
    }
  }
};

const formatRole = (role) => {
  const map = {
    'superadmin': 'Super Admin EDP',
    'technician': 'Teknisi Lapangan',
    'dept_operator': 'Operator Departemen',
    'management': 'Management Viewer',
  };
  return map[role] || role;
};

const roleBadge = (role) => {
  if (role === 'superadmin') return 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300';
  if (role === 'technician') return 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300';
  if (role === 'dept_operator') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300';
  return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300';
};

onMounted(() => {
  fetchUsers();
  fetchDepartments();
});
</script>
