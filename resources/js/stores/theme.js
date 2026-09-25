import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
    const getInitialTheme = () => {
        const saved = localStorage.getItem('cdams_theme');
        if (saved !== null) {
            return saved === 'dark';
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    const isDark = ref(getInitialTheme());

    const applyTheme = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        }
    };

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        localStorage.setItem('cdams_theme', isDark.value ? 'dark' : 'light');
        applyTheme();
    };

    // Inisialisasi awal
    applyTheme();

    return {
        isDark,
        toggleTheme,
        applyTheme,
    };
});
