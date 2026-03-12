import axios from 'axios';

const http = axios.create({
    baseURL: '',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export async function initCsrf() {
    await http.get('/sanctum/csrf-cookie');
}

export default http;