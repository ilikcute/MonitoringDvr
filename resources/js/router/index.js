import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import AppLayout from '@/layouts/AppLayout.vue';
import LoginView from '@/views/LoginView.vue';
import DashboardView from '@/views/DashboardView.vue';
import StoresListView from '@/views/StoresListView.vue';
import StoreDetailView from '@/views/StoreDetailView.vue';
import ChecksView from '@/views/ChecksView.vue';
import AuditLogsView from '@/views/AuditLogsView.vue';
import UsersView from '@/views/UsersView.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
    },
    {
        path: '/',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: DashboardView,
            },
            {
                path: 'stores',
                name: 'stores',
                component: StoresListView,
            },
            {
                path: 'stores/:id',
                name: 'store-detail',
                component: StoreDetailView,
            },
            {
                path: 'checks',
                name: 'checks',
                component: ChecksView,
            },
            {
                path: 'users',
                name: 'users',
                component: UsersView,
                meta: { requiresSuperAdmin: true },
            },
            {
                path: 'audit-logs',
                name: 'audit-logs',
                component: AuditLogsView,
                meta: { requiresSuperAdmin: true },
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
