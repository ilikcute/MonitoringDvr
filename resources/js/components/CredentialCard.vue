<template>
  <div class="relative overflow-hidden rounded-2xl border transition-all duration-200 p-4 sm:p-5 shadow-sm" :class="cardBorderClass">
    <div class="flex items-start justify-between">
      <!-- Badge Departemen & Slot -->
      <div class="flex items-center space-x-3">
        <div class="h-10 w-10 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm" :class="badgeClass">
          {{ account.department_code }}
        </div>
        <div>
          <div class="flex items-center space-x-2">
            <h4 class="font-bold text-sm sm:text-base text-slate-800 dark:text-slate-100">
              Slot {{ account.account_slot }} — {{ departmentTitle }}
            </h4>
            <span v-if="account.is_active" class="px-2 py-0.5 text-[10px] rounded-full font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
              Aktif
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {{ account.permission_profile }}
          </p>
        </div>
      </div>

      <!-- Tombol Aksi Edit -->
      <button
        v-if="canEdit"
        @click="openEditModal"
        class="text-xs font-semibold text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        title="Ubah Username / Password"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
      </button>
    </div>

    <!-- Credentials Field: Username & Password -->
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100 dark:border-slate-800/80">
      <!-- Username -->
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 flex items-center justify-between border border-slate-200/60 dark:border-slate-700/60">
        <div>
          <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Username</span>
          <span class="text-xs font-mono font-semibold text-slate-800 dark:text-slate-200">{{ account.username }}</span>
        </div>
        <button
          @click="copyText(account.username, 'user')"
          class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
          title="Salin Username"
        >
          <span v-if="copiedField === 'user'" class="text-[10px] text-emerald-600 font-bold">Tersalin!</span>
          <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
        </button>
      </div>

      <!-- Password Field (Masked / Revealed) -->
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 flex items-center justify-between border border-slate-200/60 dark:border-slate-700/60">
        <div class="overflow-hidden">
          <div class="flex items-center space-x-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Password</span>
            <span v-if="isRevealed" class="text-[10px] font-mono text-amber-600 dark:text-amber-400 font-bold">
              (Auto-hide {{ countdown }}s)
            </span>
          </div>
          <span v-if="!isRevealed" class="text-xs font-mono tracking-widest text-slate-500 select-none">
            ••••••••••••
          </span>
          <span v-else class="text-xs font-mono font-bold text-blue-700 dark:text-blue-300 break-all">
            {{ revealedPassword }}
          </span>
        </div>

        <div class="flex items-center space-x-1 pl-2">
          <!-- Tombol Reveal (Mata) -->
          <button
            type="button"
            :disabled="isRevealing"
            @click="toggleReveal"
            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-200/50 dark:hover:bg-slate-700/50 transition-colors"
            :title="isRevealed ? 'Sembunyikan' : 'Buka Password (Terekam Audit Log)'"
          >
            <span v-if="isRevealing" class="animate-spin h-4 w-4 border-2 border-blue-600 border-t-transparent rounded-full block"></span>
            <svg v-else-if="isRevealed" class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>

          <!-- Tombol Salin Password -->
          <button
            v-if="isRevealed"
            @click="copyText(revealedPassword, 'pass')"
            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-200/50 dark:hover:bg-slate-700/50 transition-colors"
            title="Salin Password"
          >
            <span v-if="copiedField === 'pass'" class="text-[10px] text-emerald-600 font-bold">OK!</span>
            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Progress Bar Countdown Auto-Hide -->
    <div v-if="isRevealed" class="mt-3 w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full overflow-hidden">
      <div
        class="bg-blue-600 h-1 transition-all duration-1000 ease-linear rounded-full"
        :style="{ width: `${(countdown / 15) * 100}%` }"
      ></div>
    </div>

    <!-- Modal Edit Kredensial Slot -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
          Update Kredensial {{ account.department_code }} (Slot {{ account.account_slot }})
        </h3>
        <form @submit.prevent="saveAccount" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Username</label>
            <input
              v-model="editForm.username"
              type="text"
              class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
              required
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
              Password Baru (Kosongkan jika tidak diubah)
            </label>
            <input
              v-model="editForm.password"
              type="text"
              class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
              placeholder="Masukkan password baru..."
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Hak Akses / Profil</label>
            <input
              v-model="editForm.permission_profile"
              type="text"
              class="w-full text-xs py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
            />
          </div>
          <div class="flex justify-end space-x-2 pt-2">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-3 py-1.5 text-xs font-semibold rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm"
            >
              {{ isSaving ? 'Menyimpan...' : 'Simpan Kredensial' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import api from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  account: { type: Object, required: true },
  dvrId: { type: [Number, String], required: true },
});

const emit = defineEmits(['updated']);
const authStore = useAuthStore();

const isRevealed = ref(false);
const isRevealing = ref(false);
const revealedPassword = ref('');
const countdown = ref(15);
let timer = null;

const copiedField = ref(null);
const showEditModal = ref(false);
const isSaving = ref(false);
const editForm = ref({
  username: props.account.username,
  password: '',
  permission_profile: props.account.permission_profile,
});

const canEdit = computed(() => {
  return authStore.isSuperAdmin || authStore.isTechnician || (authStore.isDeptOperator && authStore.departmentCode === props.account.department_code);
});

const departmentTitle = computed(() => {
  const map = {
    'IC': 'Inventory Control',
    'EDP': 'IT Support / Admin',
    'SPV': 'Supervisor Area',
    'DEV': 'Team Development',
    'AUD': 'Internal Audit',
  };
  return map[props.account.department_code] || props.account.department_name || props.account.department_code;
});

const badgeClass = computed(() => {
  const map = {
    'IC': 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300/50',
    'EDP': 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-300/50',
    'SPV': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300/50',
    'DEV': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300 border border-cyan-300/50',
    'AUD': 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border border-purple-300/50',
  };
  return map[props.account.department_code] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
});

const cardBorderClass = computed(() => {
  return isRevealed.value
    ? 'border-blue-500 ring-2 ring-blue-500/20 bg-white dark:bg-slate-900'
    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900';
});

const toggleReveal = async () => {
  if (isRevealed.value) {
    hidePassword();
    return;
  }

  isRevealing.value = true;
  try {
    const response = await api.post(`/dvrs/${props.dvrId}/accounts/${props.account.id}/reveal-password`);
    revealedPassword.value = response.data.data.decrypted_password;
    isRevealed.value = true;
    countdown.value = response.data.data.expires_in_seconds || 15;

    clearInterval(timer);
    timer = setInterval(() => {
      countdown.value--;
      if (countdown.value <= 0) {
        hidePassword();
      }
    }, 1000);
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal membuka password. Periksa otorisasi peran Anda.');
  } finally {
    isRevealing.value = false;
  }
};

const hidePassword = () => {
  isRevealed.value = false;
  revealedPassword.value = '';
  clearInterval(timer);
};

const copyText = async (text, field) => {
  try {
    await navigator.clipboard.writeText(text);
    copiedField.value = field;
    setTimeout(() => {
      copiedField.value = null;
    }, 2000);
  } catch {
    alert('Gagal menyalin teks ke clipboard.');
  }
};

const openEditModal = () => {
  editForm.value = {
    username: props.account.username,
    password: '',
    permission_profile: props.account.permission_profile,
  };
  showEditModal.value = true;
};

const saveAccount = async () => {
  isSaving.value = true;
  try {
    const response = await api.put(`/dvrs/${props.dvrId}/accounts/${props.account.id}`, editForm.value);
    showEditModal.value = false;
    emit('updated', response.data.data);
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal memperbarui kredensial akun.');
  } finally {
    isSaving.value = false;
  }
};
</script>
