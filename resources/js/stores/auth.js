import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/api/client';
import { useNetworkStore } from './network';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('cdams_token') || null);
    const user = ref(JSON.parse(localStorage.getItem('cdams_user') || 'null'));
    const isLoading = ref(false);

    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const isSuperAdmin = computed(() => user.value?.role === 'superadmin');
    const isTechnician = computed(() => user.value?.role === 'technician');
    const isDeptOperator = computed(() => user.value?.role === 'dept_operator');
    const isManagement = computed(() => user.value?.role === 'management');
    const departmentCode = computed(() => user.value?.department?.code || null);

    const login = async (email, password) => {
        isLoading.value = true;
        try {
            const response = await api.post('/auth/login', { email, password });
            const data = response.data.data;

            token.value = data.token;
            user.value = data.user;

            localStorage.setItem('cdams_token', data.token);
            localStorage.setItem('cdams_user', JSON.stringify(data.user));

            if (data.network_type) {
                const networkStore = useNetworkStore();
                networkStore.setDetectedMode(data.network_type);
            }

            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Login gagal. Periksa kembali email dan kata sandi Anda.';
            return { success: false, message };
        } finally {
            isLoading.value = false;
        }
    };

    const fetchUser = async () => {
        if (!token.value) return;
        try {
            const response = await api.get('/auth/me');
            user.value = response.data.data.user;
            localStorage.setItem('cdams_user', JSON.stringify(user.value));

            if (response.data.data.network_type) {
                const networkStore = useNetworkStore();
                networkStore.setDetectedMode(response.data.data.network_type);
            }
        } catch {
            logout();
        }
    };

    const logout = async () => {
        try {
            if (token.value) {
                await api.post('/auth/logout');
            }
        } catch {
            // Abaikan kesalahan koneksi saat logout
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('cdams_token');
            localStorage.removeItem('cdams_user');
            window.location.href = '/login';
        }
    };

    return {
        token,
        user,
        isLoading,
        isAuthenticated,
        isSuperAdmin,
        isTechnician,
        isDeptOperator,
        isManagement,
        departmentCode,
        login,
        fetchUser,
        logout,
    };
});
