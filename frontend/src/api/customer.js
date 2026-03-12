import http, { initCsrf } from './http';

export async function customerRegister(payload) {
    await initCsrf();
    const { data } = await http.post('/api/customer/register', payload);
    return data;
}

export async function customerLogin(payload) {
    await initCsrf();
    const { data } = await http.post('/api/customer/login', payload);
    return data;
}

export async function customerLogout() {
    const { data } = await http.post('/api/customer/logout');
    return data;
}

export async function fetchCustomerMe() {
    const { data } = await http.get('/api/customer/me');
    return data;
}

export async function fetchCustomerDashboard() {
    const { data } = await http.get('/api/customer/dashboard');
    return data;
}

export async function updateCustomerProfile(payload) {
    const { data } = await http.put('/api/customer/profile', payload);
    return data;
}

export async function changeCustomerPassword(payload) {
    await initCsrf();
    const { data } = await http.post('/api/customer/change-password', payload);
    return data;
}