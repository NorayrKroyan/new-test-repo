import http, { initCsrf } from './http';

export async function carrierRegister(payload) {
    await initCsrf();
    const { data } = await http.post('/api/carrier/register', payload);
    return data;
}

export async function carrierLogin(payload) {
    await initCsrf();
    const { data } = await http.post('/api/carrier/login', payload);
    return data;
}

export async function carrierLogout() {
    const { data } = await http.post('/api/carrier/logout');
    return data;
}

export async function fetchCarrierMe() {
    const { data } = await http.get('/api/carrier/me');
    return data;
}

export async function fetchCarrierDashboard() {
    const { data } = await http.get('/api/carrier/dashboard');
    return data;
}

export async function updateCarrierProfile(payload) {
    const { data } = await http.put('/api/carrier/profile', payload);
    return data;
}

export async function changeCarrierPassword(payload) {
    await initCsrf();
    const { data } = await http.post('/api/carrier/change-password', payload);
    return data;
}