<template>
    <div
        class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 transition-colors"
    >
        <div class="w-full max-w-md">
            <!-- Branding & Title -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-xl shadow-blue-600/30 mb-4"
                >
                    <svg
                        class="h-8 w-8"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                <h1
                    class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                >
                    CDAMS Portal
                </h1>
                <p
                    class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1"
                >
                    CCTV DVR Asset & Access Management System
                </p>
            </div>

            <!-- Login Card -->
            <div
                class="bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-800 transition-all"
            >
                <h2
                    class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-6 text-center"
                >
                    Masuk ke Akun Anda
                </h2>

                <div
                    v-if="errorMessage"
                    class="mb-5 p-3.5 rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-900/40 text-xs text-red-600 dark:text-red-400 flex items-center space-x-2"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ errorMessage }}</span>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5"
                        >
                            Alamat Email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all"
                            placeholder="nama@cdams.local"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5"
                        >
                            Kata Sandi
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition-all"
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="pt-3">
                        <button
                            type="submit"
                            :disabled="authStore.isLoading"
                            class="w-full py-3.5 rounded-2xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center space-x-2 disabled:opacity-50"
                        >
                            <span
                                v-if="authStore.isLoading"
                                class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"
                            ></span>
                            <span>{{
                                authStore.isLoading
                                    ? "Memverifikasi..."
                                    : "Masuk ke Sistem"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
    email: "",
    password: "",
});
const errorMessage = ref("");

const handleLogin = async () => {
    errorMessage.value = "";
    const result = await authStore.login(form.value.email, form.value.password);
    if (result.success) {
        router.push("/");
    } else {
        errorMessage.value = result.message;
    }
};
</script>
