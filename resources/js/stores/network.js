import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useNetworkStore = defineStore('network', () => {
    const simulatedMode = ref(localStorage.getItem('cdams_network_simulate') || null);
    const detectedMode = ref(localStorage.getItem('cdams_detected_network') || 'LAN');

    const currentMode = computed(() => {
        return simulatedMode.value || detectedMode.value;
    });

    const isSimulated = computed(() => {
        return simulatedMode.value !== null;
    });

    const isLan = computed(() => currentMode.value === 'LAN');
    const isWan = computed(() => currentMode.value === 'WAN');

    const setSimulatedMode = (mode) => {
        if (!mode) {
            simulatedMode.value = null;
            localStorage.removeItem('cdams_network_simulate');
        } else {
            simulatedMode.value = mode.toUpperCase();
            localStorage.setItem('cdams_network_simulate', simulatedMode.value);
        }
    };

    const toggleMode = () => {
        const nextMode = currentMode.value === 'LAN' ? 'WAN' : 'LAN';
        setSimulatedMode(nextMode);
    };

    const setDetectedMode = (mode) => {
        if (mode) {
            detectedMode.value = mode.toUpperCase();
            localStorage.setItem('cdams_detected_network', detectedMode.value);
        }
    };

    return {
        currentMode,
        simulatedMode,
        detectedMode,
        isSimulated,
        isLan,
        isWan,
        setSimulatedMode,
        toggleMode,
        setDetectedMode,
    };
});
