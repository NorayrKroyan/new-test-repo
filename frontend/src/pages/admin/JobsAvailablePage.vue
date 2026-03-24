<template>
  <AdminLayout>
    <div class="jobs-available-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Jobs Available</h1>
          </div>

          <div class="grid w-full gap-2 md:grid-cols-[minmax(260px,1fr)_auto] xl:max-w-[640px]">
            <input
                v-model="q"
                class="h-10 min-w-0 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search job"
            />

            <button
                class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white"
                @click="openCreate"
            >
              Add
            </button>
          </div>
        </div>
      </div>

      <div
          v-if="err"
          class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700 shadow-sm"
      >
        {{ err }}
      </div>

      <div class="space-y-2 md:hidden">
        <div
            v-for="row in mobileRows"
            :key="row.id"
            class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
        >
          <div class="flex items-start justify-between gap-2">
            <button
                class="text-left text-sm font-semibold leading-5 text-sky-700 hover:underline"
                @click="editRow(row)"
            >
              {{ row.job_number || '—' }}
            </button>

            <span
                class="status-badge"
                :class="statusBadgeClass(row.status)"
            >
              {{ displayStatusLabel(row.status) }}
            </span>
          </div>

          <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
            <div><span class="font-medium">Title:</span> {{ row.title || '—' }}</div>
            <div><span class="font-medium">Start:</span> {{ formatDateDisplay(row.job_start_date) }}</div>
            <div>
              <span class="font-medium">Route:</span>
              {{ row.origin_city || '—' }}, {{ row.origin_state || '—' }} →
              {{ row.destination_city || '—' }}, {{ row.destination_state || '—' }}
            </div>
            <div><span class="font-medium">Capacity:</span> {{ rosterDisplay(row) }}</div>
            <div><span class="font-medium">Rate Description:</span> {{ row.rate_description || '—' }}</div>
          </div>

          <div class="mt-3 flex justify-end">
            <button
                class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="openRoster(row)"
            >
              Manage Roster
            </button>
          </div>
        </div>

        <div
            v-if="!loading && !rows.length"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-sm"
        >
          No jobs found.
        </div>

        <div
            v-if="rows.length && mobileTotalPages > 1"
            class="rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
        >
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <div>Page {{ mobilePage }} of {{ mobileTotalPages }}</div>

            <div class="flex items-center gap-2">
              <button
                  class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="mobilePage <= 1"
                  @click="goToMobilePage(mobilePage - 1)"
              >
                Prev
              </button>

              <button
                  class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="mobilePage >= mobileTotalPages"
                  @click="goToMobilePage(mobilePage + 1)"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
          ref="tableWrap"
          class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block"
      >
        <DataTable
            class="display nowrap compact stripe row-border hover jobs-available-datatable w-full"
            :data="rows"
            :columns="columns"
            :options="options"
        />
      </div>

      <JobAvailableModal
          v-if="open"
          :key="jobModalKey"
          :open="open"
          :saving="saving"
          :deleting="deleting"
          :form="form"
          :roster-summary="activeRosterSummary"
          @close="closeModal"
          @save="saveRow"
          @delete="deleteCurrent"
          @manage-roster="openRosterFromForm"
      />

      <RosterModal
          v-if="rosterOpen && rosterJob"
          :open="rosterOpen"
          :job="rosterJob"
          @close="closeRoster"
          @updated="onRosterUpdated"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net'
import Responsive from 'datatables.net-responsive'
import FixedHeader from 'datatables.net-fixedheader'
import AdminLayout from '../../layouts/AdminLayout.vue'
import JobAvailableModal from '../../components/admin/JobAvailableModal.vue'
import RosterModal from '../../components/admin/RosterModal.vue'
import { deleteJobAvailable, fetchJobsAvailable, saveJobAvailable } from '../../api/admin'

DataTable.use(DataTablesCore)
DataTable.use(Responsive)
DataTable.use(FixedHeader)

const q = ref('')
const rows = ref([])
const open = ref(false)
const rosterOpen = ref(false)
const rosterJob = ref(null)
const saving = ref(false)
const deleting = ref(false)
const loading = ref(false)
const err = ref('')
const tableWrap = ref(null)
const jobModalKey = ref(0)

const mobilePage = ref(1)
const mobilePageSize = ref(10)

let searchTimer = null
let loadSeq = 0

const form = reactive({
  id: null,
  job_number: '',
  title: '',
  description: '',
  origin_city: '',
  origin_state: '',
  destination_city: '',
  destination_state: '',
  equipment_type: '',
  trailer_type: '',
  weight: '',
  rate_description: '',
  status: 'open',
  job_start_date: '',
  primary_required: 0,
  spare_allowed: 0,
})

const mobileTotalPages = computed(() => {
  const total = Math.ceil(rows.value.length / mobilePageSize.value)
  return total > 0 ? total : 1
})

const mobileRows = computed(() => {
  const start = (mobilePage.value - 1) * mobilePageSize.value
  const end = start + mobilePageSize.value
  return rows.value.slice(start, end)
})

const activeRosterSummary = computed(() => {
  const row = rows.value.find((item) => Number(item.id) === Number(form.id || 0))
  return row?.roster_summary || {
    primary_required: Number(form.primary_required || 0),
    primary_filled: 0,
    spare_allowed: Number(form.spare_allowed || 0),
    spare_filled: 0,
    primary_overfill: 0,
    spare_overfill: 0,
  }
})

function goToMobilePage(page) {
  if (page < 1) {
    mobilePage.value = 1
    return
  }

  if (page > mobileTotalPages.value) {
    mobilePage.value = mobileTotalPages.value
    return
  }

  mobilePage.value = page
}

function resetForm() {
  Object.assign(form, {
    id: null,
    job_number: '',
    title: '',
    description: '',
    origin_city: '',
    origin_state: '',
    destination_city: '',
    destination_state: '',
    equipment_type: '',
    trailer_type: '',
    weight: '',
    rate_description: '',
    status: 'open',
    job_start_date: '',
    primary_required: 0,
    spare_allowed: 0,
  })
}

function extractErrorMessage(e) {
  if (e?.response?.data?.errors) {
    const firstField = Object.values(e.response.data.errors)[0]
    if (Array.isArray(firstField) && firstField[0]) {
      return firstField[0]
    }
  }

  return e?.response?.data?.message || e?.message || 'Request failed'
}

function esc(value) {
  return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;')
}

function displayValue(value) {
  if (value === null || value === undefined || String(value).trim() === '') {
    return '—'
  }

  return String(value)
}

function sortValue(value) {
  const normalized = displayValue(value)
  return normalized === '—' ? '' : normalized
}

function formatDateDisplay(value) {
  const raw = String(value ?? '').trim()

  if (!raw) {
    return '—'
  }

  const datePart = raw.split('T')[0].replaceAll('/', '-')

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    const [year, month, day] = datePart.split('-')
    return `${month}-${day}-${year}`
  }

  if (/^\d{2}-\d{2}-\d{4}$/.test(datePart)) {
    return datePart
  }

  return raw
}

function dateSortValue(value) {
  const raw = String(value ?? '').trim()

  if (!raw) {
    return ''
  }

  const datePart = raw.split('T')[0].replaceAll('/', '-')

  if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
    return datePart
  }

  if (/^\d{2}-\d{2}-\d{4}$/.test(datePart)) {
    const [month, day, year] = datePart.split('-')
    return `${year}-${month}-${day}`
  }

  return raw
}

function normalizeStatus(status) {
  return String(status ?? '').trim().toLowerCase()
}

function toTitleWords(status) {
  return String(status ?? '')
      .trim()
      .replaceAll('_', ' ')
      .replace(/\s+/g, ' ')
      .replace(/\b\w/g, (char) => char.toUpperCase())
}

function getStatusMeta(status) {
  const key = normalizeStatus(status)

  if (key === 'open') {
    return {
      label: 'Open',
      className: 'status-badge--open',
    }
  }

  if (key === 'filled') {
    return {
      label: 'Filled',
      className: 'status-badge--filled',
    }
  }

  if (key === 'closed') {
    return {
      label: 'Closed',
      className: 'status-badge--closed',
    }
  }

  if (!key) {
    return {
      label: 'Open',
      className: 'status-badge--open',
    }
  }

  return {
    label: toTitleWords(key),
    className: 'status-badge--default',
  }
}

function displayStatusLabel(status) {
  return getStatusMeta(status).label
}

function statusBadgeClass(status) {
  return getStatusMeta(status).className
}

function renderStatusBadge(status) {
  const meta = getStatusMeta(status)
  return `<span class="status-badge ${meta.className}">${esc(meta.label)}</span>`
}

function rosterDisplay(row) {
  const summary = row?.roster_summary || {}
  return `${summary.primary_filled || 0} / ${summary.primary_required || 0} + ${summary.spare_filled || 0}`
}

function rosterBadgeClass(row) {
  const summary = row?.roster_summary || {}

  if ((summary.primary_overfill || 0) > 0 || (summary.spare_overfill || 0) > 0) {
    return 'roster-badge roster-badge--danger'
  }

  if ((summary.primary_filled || 0) >= (summary.primary_required || 0) && (summary.primary_required || 0) > 0) {
    return 'roster-badge roster-badge--warn'
  }

  return 'roster-badge roster-badge--info'
}

function buildRouteLabel(row) {
  return `${displayValue(row.origin_city)}, ${displayValue(row.origin_state)} → ${displayValue(row.destination_city)}, ${displayValue(row.destination_state)}`
}

const columns = [
  {
    title: 'Job #',
    data: 'job_number',
    render: (data, type, row) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return `
        <button
          type="button"
          class="job-edit-link"
          data-id="${row.id}"
        >
          ${esc(value)}
        </button>
      `
    },
  },
  {
    title: 'Title',
    data: 'title',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Start Date',
    data: 'job_start_date',
    className: 'dt-start-date-col',
    width: '120px',
    render: (data, type) => {
      if (type === 'sort' || type === 'type') {
        return dateSortValue(data)
      }

      const value = formatDateDisplay(data)

      if (type === 'filter') {
        return value === '—' ? '' : value
      }

      return esc(value)
    },
  },
  {
    title: 'Route',
    data: null,
    className: 'dt-route-col',
    render: (_data, type, row) => {
      const value = buildRouteLabel(row)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(`${row.origin_city || ''} ${row.origin_state || ''} ${row.destination_city || ''} ${row.destination_state || ''}`)
      }

      return esc(value)
    },
  },
  {
    title: 'Capacity',
    data: null,
    render: (_data, type, row) => {
      const value = rosterDisplay(row)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return `<span class="${rosterBadgeClass(row)}">${esc(value)}</span>`
    },
  },
  {
    title: 'Status',
    data: 'status',
    render: (data, type) => {
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(displayStatusLabel(data))
      }

      return renderStatusBadge(data)
    },
  },
  {
    title: 'Roster',
    data: null,
    orderable: false,
    searchable: false,
    render: (_data, _type, row) => {
      return `
        <button
          type="button"
          class="job-roster-link"
          data-id="${row.id}"
        >
          Manage Roster
        </button>
      `
    },
  },
]

const options = {
  paging: true,
  pageLength: 25,
  lengthMenu: [
    [25, 50, 100, -1],
    [25, 50, 100, 'All'],
  ],
  searching: false,
  ordering: true,
  info: true,
  responsive: false,
  fixedHeader: true,
  autoWidth: false,
  scrollX: true,
  order: [[2, 'asc']],
  language: {
    emptyTable: 'No jobs found',
    zeroRecords: 'No jobs found',
  },
}

async function loadRows() {
  const seq = ++loadSeq
  loading.value = true
  err.value = ''

  try {
    const data = await fetchJobsAvailable({ q: q.value })

    if (seq !== loadSeq) return

    rows.value = Array.isArray(data?.data) ? [...data.data] : []
    mobilePage.value = 1
  } catch (e) {
    if (seq !== loadSeq) return

    err.value = extractErrorMessage(e)
    rows.value = []
    mobilePage.value = 1
  } finally {
    if (seq === loadSeq) {
      loading.value = false
    }
  }
}

function openCreate() {
  resetForm()
  jobModalKey.value += 1
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    job_number: row.job_number || '',
    title: row.title || '',
    description: row.description || '',
    origin_city: row.origin_city || '',
    origin_state: row.origin_state || '',
    destination_city: row.destination_city || '',
    destination_state: row.destination_state || '',
    equipment_type: row.equipment_type || '',
    trailer_type: row.trailer_type || '',
    weight: row.weight || '',
    rate_description: row.rate_description || '',
    status: row.status || 'open',
    job_start_date: row.job_start_date || '',
    primary_required: row.primary_required || 0,
    spare_allowed: row.spare_allowed || 0,
  })

  jobModalKey.value += 1
  open.value = true
}

function editRowById(id) {
  const row = rows.value.find((item) => Number(item.id) === Number(id))
  if (row) {
    editRow(row)
  }
}

function openRoster(row) {
  rosterJob.value = row
  rosterOpen.value = true
}

function openRosterById(id) {
  const row = rows.value.find((item) => Number(item.id) === Number(id))
  if (row) {
    openRoster(row)
  }
}

function openRosterFromForm() {
  const row = rows.value.find((item) => Number(item.id) === Number(form.id || 0))
  if (row) {
    openRoster(row)
  }
}

function closeModal() {
  open.value = false
}

function closeRoster() {
  rosterOpen.value = false
  rosterJob.value = null
}

function onRosterUpdated(summary) {
  if (!rosterJob.value?.id) return

  rows.value = rows.value.map((row) => {
    if (Number(row.id) !== Number(rosterJob.value.id)) {
      return row
    }

    const updated = {
      ...row,
      roster_summary: {
        ...(row.roster_summary || {}),
        ...(summary || {}),
      },
    }

    rosterJob.value = updated

    if (Number(form.id || 0) === Number(updated.id)) {
      form.primary_required = updated.primary_required || form.primary_required
      form.spare_allowed = updated.spare_allowed || form.spare_allowed
    }

    return updated
  })
}

async function saveRow() {
  saving.value = true
  err.value = ''

  try {
    await saveJobAvailable({
      job_number: form.job_number || null,
      title: form.title,
      description: form.description || null,
      origin_city: form.origin_city || null,
      origin_state: form.origin_state || null,
      destination_city: form.destination_city || null,
      destination_state: form.destination_state || null,
      equipment_type: form.equipment_type || null,
      trailer_type: form.trailer_type || null,
      weight: form.weight || null,
      rate_description: form.rate_description || null,
      status: form.status || 'open',
      job_start_date: form.job_start_date || null,
      primary_required: form.primary_required || 0,
      spare_allowed: form.spare_allowed || 0,
    }, form.id)
    open.value = false
    resetForm()
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  } finally {
    saving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  err.value = ''

  try {
    await deleteJobAvailable(form.id)
    open.value = false
    resetForm()
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  } finally {
    deleting.value = false
  }
}

function handleTableClick(event) {
  const editBtn = event.target.closest('.job-edit-link')
  if (editBtn) {
    event.preventDefault()
    editRowById(editBtn.getAttribute('data-id'))
    return
  }

  const rosterBtn = event.target.closest('.job-roster-link')
  if (rosterBtn) {
    event.preventDefault()
    openRosterById(rosterBtn.getAttribute('data-id'))
  }
}

watch(q, () => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    loadRows()
  }, 300)
})

onMounted(() => {
  loadRows()

  if (tableWrap.value) {
    tableWrap.value.addEventListener('click', handleTableClick)
  }
})

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  if (tableWrap.value) {
    tableWrap.value.removeEventListener('click', handleTableClick)
  }
})
</script>

<style>
@import "datatables.net-dt";
@import "datatables.net-responsive-dt";
@import "datatables.net-fixedheader-dt";

.jobs-available-page .top-card {
  padding-top: 6px;
  padding-bottom: 6px;
}

.jobs-available-page .dt-container {
  padding: 0;
}

.jobs-available-page .dt-container .dt-layout-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 0;
  padding: 2px 8px;
}

.jobs-available-page .dt-container .dt-layout-row:first-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.jobs-available-page .dt-container .dt-layout-row:last-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.jobs-available-page .dt-container .dt-layout-cell {
  margin: 0;
}

.jobs-available-page .dt-container .dt-length,
.jobs-available-page .dt-container .dt-info,
.jobs-available-page .dt-container .dt-paging {
  font-size: 12px;
  color: #475569;
}

.jobs-available-page .dt-container .dt-length label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  font-size: 12px;
  color: #475569;
  margin: 0;
}

.jobs-available-page .dt-container .dt-length select {
  min-width: 76px;
  height: 28px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  padding: 0 28px 0 10px;
  font-size: 12px;
  color: #0f172a;
  line-height: 28px;
  margin: 0;
  vertical-align: middle;
}

.jobs-available-page .dt-container .dt-info {
  white-space: nowrap;
}

.jobs-available-page .dt-container .dt-paging {
  white-space: nowrap;
}

.jobs-available-page .dt-container .dt-paging .dt-paging-button {
  min-width: 28px;
  height: 28px;
  margin: 0 2px;
  border: 1px solid #cbd5e1 !important;
  border-radius: 9px;
  background: #fff !important;
  color: #334155 !important;
  box-shadow: none !important;
  line-height: 26px;
  padding: 0 8px !important;
}

.jobs-available-page .dt-container .dt-paging .dt-paging-button.current,
.jobs-available-page .dt-container .dt-paging .dt-paging-button.current:hover {
  border-color: #0f172a !important;
  background: #0f172a !important;
  color: #fff !important;
}

.jobs-available-page .dt-container .dt-paging .dt-paging-button:hover {
  background: #f8fafc !important;
  color: #0f172a !important;
}

.jobs-available-page .dt-scroll-head table.dataTable,
.jobs-available-page .dt-scroll-body table.dataTable,
.jobs-available-page table.dataTable {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.jobs-available-page table.dataTable thead th {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  color: #64748b !important;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0 !important;
}

.jobs-available-page table.dataTable tbody td {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 2px !important;
  padding-bottom: 2px !important;
  vertical-align: middle;
  line-height: 1.05rem;
}

.jobs-available-page table.dataTable thead th:nth-child(3),
.jobs-available-page table.dataTable tbody td:nth-child(3),
.jobs-available-page table.dataTable thead th.dt-start-date-col,
.jobs-available-page table.dataTable tbody td.dt-start-date-col {
  min-width: 120px !important;
  width: 120px !important;
  text-align: center !important;
}

.jobs-available-page table.dataTable thead th:nth-child(4),
.jobs-available-page table.dataTable tbody td:nth-child(4),
.jobs-available-page table.dataTable thead th.dt-route-col,
.jobs-available-page table.dataTable tbody td.dt-route-col {
  text-align: center !important;
}

.jobs-available-page table.dataTable tbody tr:hover {
  background: #f8fafc;
}

.jobs-available-page .job-edit-link {
  border: 0;
  background: transparent;
  padding: 0;
  color: #0369a1;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  text-align: left;
  white-space: nowrap;
}

.jobs-available-page .job-edit-link:hover {
  text-decoration: underline;
}

.jobs-available-page .job-roster-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  background: #fff;
  padding: 0 10px;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  white-space: nowrap;
}

.jobs-available-page .job-roster-link:hover {
  background: #f8fafc;
}

.jobs-available-page .status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  border: 1px solid transparent;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.jobs-available-page .status-badge--open {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.jobs-available-page .status-badge--filled {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.jobs-available-page .status-badge--closed {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.jobs-available-page .status-badge--default {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.jobs-available-page .roster-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  border: 1px solid transparent;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.jobs-available-page .roster-badge--info {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.jobs-available-page .roster-badge--warn {
  background: #fffbeb;
  border-color: #fcd34d;
  color: #b45309;
}

.jobs-available-page .roster-badge--danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}

@media (max-width: 767px) {
  .jobs-available-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>