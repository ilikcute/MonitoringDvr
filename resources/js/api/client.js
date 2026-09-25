import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

// Request Interceptor: Sisipkan Token Sanctum dan Simulasi Network jika ada
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('cdams_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const simulateMode = localStorage.getItem('cdams_network_simulate');
    if (simulateMode) {
        config.headers['X-Network-Simulate'] = simulateMode;
    }

    return config;
});

// Response Interceptor: Tangani 401 Unauthorized secara terpusat
api.interceptors.response.use(
    (response) => {
        // Tangkap mode jaringan dari header response jika ada
        const networkMode = response.headers['x-network-mode'];
        if (networkMode && !localStorage.getItem('cdams_network_simulate')) {
            localStorage.setItem('cdams_detected_network', networkMode);
        }
        return response;
    },
    (error) => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('cdams_token');
            localStorage.removeItem('cdams_user');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
