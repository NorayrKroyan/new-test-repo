import { createRouter, createWebHistory } from 'vue-router';
import { fetchAdminMe } from '../api/admin';
import { fetchCarrierMe } from '../api/carrier';
import { fetchCustomerMe } from '../api/customer';

import AdminLoginPage from '../pages/admin/AdminLoginPage.vue';
import AdminDashboardPage from '../pages/admin/AdminDashboardPage.vue';
import AdminUsersPage from '../pages/admin/AdminUsersPage.vue';
import AdminCustomersPage from '../pages/admin/AdminCustomersPage.vue';
import LeadsPage from '../pages/admin/LeadsPage.vue';
import CarriersPage from '../pages/admin/CarriersPage.vue';
import JobsAvailablePage from '../pages/admin/JobsAvailablePage.vue';

import PublicLoginPage from '../pages/PublicLoginPage.vue';
import PublicRegisterPage from '../pages/PublicRegisterPage.vue';

import CarrierDashboardPage from '../pages/carrier/CarrierDashboardPage.vue';
import CustomerDashboardPage from '../pages/customer/CustomerDashboardPage.vue';

const routes = [
    { path: '/', redirect: '/login' },

    { path: '/login', name: 'public-login', component: PublicLoginPage, meta: { publicGuest: true } },
    { path: '/register', name: 'public-register', component: PublicRegisterPage, meta: { publicGuest: true } },

    { path: '/admin/login', name: 'admin-login', component: AdminLoginPage, meta: { guestFor: 'admin' } },
    { path: '/admin/dashboard', name: 'admin-dashboard', component: AdminDashboardPage, meta: { requiresRole: 'admin' } },
    { path: '/admin/admin-users', name: 'admin-users', component: AdminUsersPage, meta: { requiresRole: 'admin' } },
    { path: '/admin/customers', name: 'admin-customers', component: AdminCustomersPage, meta: { requiresRole: 'admin' } },
    { path: '/admin/leads', name: 'admin-leads', component: LeadsPage, meta: { requiresRole: 'admin' } },
    { path: '/admin/carriers', name: 'admin-carriers', component: CarriersPage, meta: { requiresRole: 'admin' } },
    { path: '/admin/jobs-available', name: 'admin-jobs-available', component: JobsAvailablePage, meta: { requiresRole: 'admin' } },

    { path: '/carrier/dashboard', name: 'carrier-dashboard', component: CarrierDashboardPage, meta: { requiresRole: 'carrier' } },
    { path: '/customer/dashboard', name: 'customer-dashboard', component: CustomerDashboardPage, meta: { requiresRole: 'customer' } },

    { path: '/carrier/login', redirect: '/login?role=carrier' },
    { path: '/carrier/register', redirect: '/register?role=carrier' },
    { path: '/customer/login', redirect: '/login?role=customer' },
    { path: '/customer/register', redirect: '/register?role=customer' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

async function checkRole(role) {
    if (role === 'admin') return fetchAdminMe();
    if (role === 'carrier') return fetchCarrierMe();
    if (role === 'customer') return fetchCustomerMe();
    throw new Error('Unknown role');
}

function dashboardPath(role) {
    if (role === 'admin') return '/admin/dashboard';
    if (role === 'carrier') return '/carrier/dashboard';
    if (role === 'customer') return '/customer/dashboard';
    return '/';
}

function loginPath(role) {
    if (role === 'admin') return '/admin/login';
    if (role === 'carrier' || role === 'customer') return '/login';
    return '/login';
}

router.beforeEach(async (to) => {
    if (to.meta.requiresRole) {
        try {
            await checkRole(to.meta.requiresRole);
            return true;
        } catch {
            return loginPath(to.meta.requiresRole);
        }
    }

    if (to.meta.guestFor) {
        try {
            await checkRole(to.meta.guestFor);
            return dashboardPath(to.meta.guestFor);
        } catch {
            return true;
        }
    }

    if (to.meta.publicGuest) {
        try {
            await fetchCustomerMe();
            return '/customer/dashboard';
        } catch {}

        try {
            await fetchCarrierMe();
            return '/carrier/dashboard';
        } catch {}

        return true;
    }

    return true;
});

export default router;