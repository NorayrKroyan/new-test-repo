// frontend/src/api/admin.js
import http, { initCsrf } from './http'

export function adminGoogleLoginUrl() {
    return '/api/auth/google/redirect/admin'
}

export async function adminLogin(payload) {
    await initCsrf()
    const { data } = await http.post('/api/admin/login', payload)
    return data
}

export async function adminLogout() {
    const { data } = await http.post('/api/admin/logout')
    return data
}

export async function fetchAdminMe() {
    const { data } = await http.get('/api/admin/me')
    return data
}

export async function fetchDashboard() {
    const { data } = await http.get('/api/admin/dashboard')
    return data
}

export async function fetchAdminUsers(params = {}) {
    const { data } = await http.get('/api/admin/admin-users', { params })
    return data
}

export async function saveAdminUser(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/admin-users/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/admin-users', payload)
    return data
}

export async function deleteAdminUser(id) {
    const { data } = await http.delete(`/api/admin/admin-users/${id}`)
    return data
}

export async function fetchLeads(params = {}) {
    const { data } = await http.get('/api/admin/leads', { params })
    return data
}

export async function fetchLeadAdNames(params = {}) {
    const { data } = await http.get('/api/admin/leads/ad-names', { params })
    return data
}

export async function fetchLeadMergePreview(id) {
    const { data } = await http.get(`/api/admin/leads/${id}/merge-preview`)
    return data
}

export async function fetchLeadFunnelSummary(params = {}) {
    const { data } = await http.get('/api/admin/leads/funnel-summary', { params })
    return data
}

export async function fetchLeadFunnelChartHtml(params = {}) {
    const response = await http.get('/api/admin/leads/funnel-chart', {
        params,
        responseType: 'text',
    })

    return response.data
}

export async function fetchLeadMapMarkers(params = {}) {
    const { data } = await http.get('/api/admin/lead-map/markers', { params })
    return data
}

export async function geocodeLeadMapMissing(payload = {}) {
    const { data } = await http.post('/api/admin/lead-map/geocode-missing', payload)
    return data
}

export async function geocodeLeadMapLead(id, payload = {}) {
    const { data } = await http.post(`/api/admin/lead-map/leads/${id}/geocode`, payload)
    return data
}

export async function mergeLeadGroup(id, payload) {
    const { data } = await http.post(`/api/admin/leads/${id}/merge`, payload)
    return data
}

export async function saveLead(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/leads/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/leads', payload)
    return data
}

export async function syncLeadContact(id) {
    const { data } = await http.post(`/api/admin/leads/${id}/sync-contact`)
    return data
}

export async function fetchLeadCallHistory(id, params = {}) {
    const { data } = await http.get(`/api/admin/leads/${id}/call-history`, { params })
    return data
}

export async function fetchLeadSmsHistory(id, params = {}) {
    const { data } = await http.get(`/api/admin/leads/${id}/sms-history`, { params })
    return data
}

export async function deleteLead(id) {
    const { data } = await http.delete(`/api/admin/leads/${id}`)
    return data
}

export async function convertLeadToCarrier(id) {
    const { data } = await http.post(`/api/admin/leads/${id}/convert-to-carrier`)
    return data
}

export async function markLeadDuplicate(id, payload) {
    const { data } = await http.post(`/api/admin/leads/${id}/mark-duplicate`, payload)
    return data
}

export async function unmarkLeadDuplicate(id) {
    const { data } = await http.post(`/api/admin/leads/${id}/unmark-duplicate`)
    return data
}

export async function runLeadAutoDedup(payload = {}) {
    const { data } = await http.post('/api/admin/leads/auto-dedup', payload)
    return data
}

export async function fetchStages(params = {}) {
    const { data } = await http.get('/api/admin/stages', { params })
    return data
}

export async function saveStage(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/stages/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/stages', payload)
    return data
}

export async function deleteStage(id) {
    const { data } = await http.delete(`/api/admin/stages/${id}`)
    return data
}

export async function fetchQualificationScripts(params = {}) {
    const { data } = await http.get('/api/admin/qualification-scripts', { params })
    return data
}

export async function fetchQualificationScript(id) {
    const { data } = await http.get(`/api/admin/qualification-scripts/${id}`)
    return data
}

export async function saveQualificationScript(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/qualification-scripts/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/qualification-scripts', payload)
    return data
}

export async function saveQualificationScriptBuilder(id, payload) {
    const { data } = await http.put(`/api/admin/qualification-scripts/${id}/builder`, payload)
    return data
}

export async function cloneQualificationScript(id) {
    const { data } = await http.post(`/api/admin/qualification-scripts/${id}/clone`)
    return data
}

export async function deleteQualificationScript(id) {
    const { data } = await http.delete(`/api/admin/qualification-scripts/${id}`)
    return data
}

export async function fetchCarriers(params = {}) {
    const { data } = await http.get('/api/admin/carriers', { params })
    return data
}

export async function saveCarrier(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/carriers/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/carriers', payload)
    return data
}

export async function deleteCarrier(id) {
    const { data } = await http.delete(`/api/admin/carriers/${id}`)
    return data
}

export async function fetchCustomers(params = {}) {
    const { data } = await http.get('/api/admin/customers', { params })
    return data
}

export async function saveCustomer(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/customers/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/customers', payload)
    return data
}

export async function deleteCustomer(id) {
    const { data } = await http.delete(`/api/admin/customers/${id}`)
    return data
}

export async function fetchJobsAvailable(params = {}) {
    const { data } = await http.get('/api/admin/jobs-available', { params })
    return data
}

export async function saveJobAvailable(payload, id = null) {
    if (id) {
        const { data } = await http.put(`/api/admin/jobs-available/${id}`, payload)
        return data
    }

    const { data } = await http.post('/api/admin/jobs-available', payload)
    return data
}

export async function deleteJobAvailable(id) {
    const { data } = await http.delete(`/api/admin/jobs-available/${id}`)
    return data
}

export async function startLeadQualification(id, payload = {}) {
    const { data } = await http.post(`/api/admin/leads/${id}/qualification-sessions/start`, payload)
    return data
}

export async function fetchLeadQualificationSessions(leadId) {
    const { data } = await http.get(`/api/admin/leads/${leadId}/qualification-sessions`)
    return data
}

export async function fetchLeadQualificationSession(sessionId) {
    const { data } = await http.get(`/api/admin/qualification-sessions/${sessionId}`)
    return data
}

export async function saveLeadQualificationAnswer(sessionId, payload) {
    const { data } = await http.post(`/api/admin/qualification-sessions/${sessionId}/answers`, payload)
    return data
}

export async function completeLeadQualificationSession(sessionId) {
    const { data } = await http.post(`/api/admin/qualification-sessions/${sessionId}/complete`)
    return data
}

export async function applyLeadQualificationStage(sessionId) {
    const { data } = await http.post(`/api/admin/qualification-sessions/${sessionId}/apply-recommended-stage`)
    return data
}

export async function fetchJobRoster(jobId, params = {}) {
    const { data } = await http.get(`/api/admin/jobs-available/${jobId}/roster`, { params })
    return data
}

export async function createJobAssignment(jobId, payload) {
    const { data } = await http.post(`/api/admin/jobs-available/${jobId}/roster`, payload)
    return data
}

export async function updateJobAssignment(id, payload) {
    const { data } = await http.put(`/api/admin/job-assignments/${id}`, payload)
    return data
}

export async function deleteJobAssignment(id) {
    const { data } = await http.delete(`/api/admin/job-assignments/${id}`)
    return data
}