import http, { initCsrf } from './http';

export async function adminLogin(payload) {
    await initCsrf();
    const { data } = await http.post('/api/admin/login', payload);
    return data;
}

export async function adminLogout() {
    const { data } = await http.post('/api/admin/logout');
    return data;
}

export async function fetchAdminMe() {
    const { data } = await http.get('/api/admin/me');
    return data;
}

export async function fetchDashboard() {
    const { data } = await http.get('/api/admin/dashboard');
    return data;
}

export async function fetchAdminUsers(params = {}) {
    const { data } = await http.get('/api/admin/admin-users', { params });
    return data;
}

export async function saveAdminUser(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/admin-users/${id}`, payload);
        return data;
    }
    const { data } = await http.post('/api/admin/admin-users', payload);
    return data;
}

export async function deleteAdminUser(id) {
    const { data } = await http.delete(`/api/admin/admin-users/${id}`);
    return data;
}

export async function fetchLeads(params = {}) {
    const { data } = await http.get('/api/admin/leads', { params });
    return data;
}

export async function saveLead(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/leads/${id}`, payload);
        return data;
    }
    const { data } = await http.post('/api/admin/leads', payload);
    return data;
}

export async function deleteLead(id) {
    const { data } = await http.delete(`/api/admin/leads/${id}`);
    return data;
}

export async function convertLeadToCarrier(id) {
    const { data } = await http.post(`/api/admin/leads/${id}/convert-to-carrier`);
    return data;
}

export async function fetchCarriers(params = {}) {
    const { data } = await http.get('/api/admin/carriers', { params });
    return data;
}

export async function saveCarrier(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/carriers/${id}`, payload);
        return data;
    }
    const { data } = await http.post('/api/admin/carriers', payload);
    return data;
}

export async function deleteCarrier(id) {
    const { data } = await http.delete(`/api/admin/carriers/${id}`);
    return data;
}

export async function fetchCustomers(params = {}) {
    const { data } = await http.get('/api/admin/customers', { params });
    return data;
}

export async function saveCustomer(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/customers/${id}`, payload);
        return data;
    }
    const { data } = await http.post('/api/admin/customers', payload);
    return data;
}

export async function deleteCustomer(id) {
    const { data } = await http.delete(`/api/admin/customers/${id}`);
    return data;
}

export async function fetchJobsAvailable(params = {}) {
    const { data } = await http.get('/api/admin/jobs-available', { params });
    return data;
}

export async function saveJobAvailable(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/jobs-available/${id}`, payload);
        return data;
    }
    const { data } = await http.post('/api/admin/jobs-available', payload);
    return data;
}

export async function deleteJobAvailable(id) {
    const { data } = await http.delete(`/api/admin/jobs-available/${id}`);
    return data;
}