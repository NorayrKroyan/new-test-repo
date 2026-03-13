<template>
  <AdminLayout>
    <div class="leads-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Leads</h1>
          </div>

          <div class="grid w-full gap-2 sm:grid-cols-[minmax(260px,1fr)_auto] xl:max-w-[640px]">
            <input
                v-model="q"
                class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search lead"
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

      <!-- Mobile cards -->
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
              {{ row.full_name || '—' }}
            </button>

            <span
                class="status-badge"
                :class="statusBadgeClass(row.lead_status)"
            >
              {{ displayStatusLabel(row.lead_status) }}
            </span>
          </div>

          <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
            <div class="break-all"><span class="font-medium">Email:</span> {{ row.email || '—' }}</div>
            <div><span class="font-medium">Platform:</span> {{ row.platform || '—' }}</div>
            <div><span class="font-medium">City:</span> {{ row.city || '—' }}</div>
            <div><span class="font-medium">Insurance:</span> {{ row.insurance_answer || '—' }}</div>
          </div>

          <button
              v-if="normalizeStatus(row.lead_status) !== 'converted_to_carrier'"
              class="mt-2 w-full rounded-xl border border-sky-300 px-3 py-1.5 text-sm font-medium text-sky-700 hover:bg-sky-50"
              @click="convertRow(row.id)"
          >
            Convert to Carrier
          </button>
        </div>

        <div
            v-if="!loading && !rows.length"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-sm"
        >
          No leads found.
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

      <!-- Desktop DataTable -->
      <div
          ref="tableWrap"
          class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block"
      >
        <DataTable
            class="display nowrap compact stripe row-border hover leads-datatable w-full"
            :data="rows"
            :columns="columns"
            :options="options"
        />
      </div>

      <LeadModal
          :open="open"
          :saving="saving"
          :deleting="deleting"
          :form="form"
          @close="closeModal"
          @save="saveRow"
          @delete="deleteCurrent"
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
import LeadModal from '../../components/admin/LeadModal.vue'
import { convertLeadToCarrier, deleteLead, fetchLeads, saveLead } from '../../api/admin'

DataTable.use(DataTablesCore)
DataTable.use(Responsive)
DataTable.use(FixedHeader)

const q = ref('')
const rows = ref([])
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)
const loading = ref(false)
const err = ref('')
const tableWrap = ref(null)

const mobilePage = ref(1)
const mobilePageSize = ref(10)

let searchTimer = null
let loadSeq = 0

const form = reactive({
  id: null,
  source_name: '',
  ad_name: '',
  platform: '',
  source_created_at: '',
  lead_date_choice: '',
  insurance_answer: '',
  full_name: '',
  email: '',
  phone: '',
  city: '',
  state: '',
  carrier_class: '',
  usdot: '',
  truck_count: '',
  trailer_count: '',
  lead_status: 'new',
  notes: '',
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

function goToMobilePage(page) {
  if (page < 1) page = 1
  if (page > mobileTotalPages.value) page = mobileTotalPages.value
  mobilePage.value = page
}

function resetForm() {
  Object.assign(form, {
    id: null,
    source_name: '',
    ad_name: '',
    platform: '',
    source_created_at: '',
    lead_date_choice: '',
    insurance_answer: '',
    full_name: '',
    email: '',
    phone: '',
    city: '',
    state: '',
    carrier_class: '',
    usdot: '',
    truck_count: '',
    trailer_count: '',
    lead_status: 'new',
    notes: '',
  })
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

  if (key === 'new') {
    return {
      label: 'New',
      className: 'status-badge--new',
    }
  }

  if (key === 'converted_to_carrier') {
    return {
      label: 'Converted',
      className: 'status-badge--converted',
    }
  }

  if (key === 'contacted') {
    return {
      label: 'Contacted',
      className: 'status-badge--contacted',
    }
  }

  if (key === 'qualified') {
    return {
      label: 'Qualified',
      className: 'status-badge--qualified',
    }
  }

  if (!key) {
    return {
      label: '—',
      className: 'status-badge--default',
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

const columns = [
  {
    title: 'Lead',
    data: null,
    render: (_data, type, row) => {
      const name = displayValue(row.full_name)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(name)
      }

      return `
        <button
          type="button"
          class="lead-edit-link"
          data-id="${row.id}"
        >
          ${esc(name)}
        </button>
      `
    },
  },
  {
    title: 'Email',
    data: 'email',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Platform',
    data: 'platform',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'City',
    data: 'city',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Insurance',
    data: 'insurance_answer',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Status',
    data: null,
    render: (_data, type, row) => {
      const rawStatus = row.lead_status
      const normalized = normalizeStatus(rawStatus)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(displayStatusLabel(rawStatus))
      }

      const convertButton = normalized !== 'converted_to_carrier'
          ? `
          <button
            type="button"
            class="lead-convert-link"
            data-id="${row.id}"
          >
            Convert to Carrier
          </button>
        `
          : ''

      return `
        <div class="lead-status-cell">
          ${renderStatusBadge(rawStatus)}
          ${convertButton}
        </div>
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
  order: [[0, 'asc']],
  language: {
    emptyTable: 'No leads found',
    zeroRecords: 'No leads found',
  },
}

async function loadRows() {
  const seq = ++loadSeq
  loading.value = true
  err.value = ''

  try {
    const data = await fetchLeads({ q: q.value })

    if (seq !== loadSeq) return

    rows.value = Array.isArray(data?.data) ? [...data.data] : []
    mobilePage.value = 1
  } catch (e) {
    if (seq !== loadSeq) return

    err.value = e?.response?.data?.message || e?.message || 'Failed to load leads'
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
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    source_name: row.source_name || '',
    ad_name: row.ad_name || '',
    platform: row.platform || '',
    source_created_at: row.source_created_at || '',
    lead_date_choice: row.lead_date_choice || '',
    insurance_answer: row.insurance_answer || '',
    full_name: row.full_name || '',
    email: row.email || '',
    phone: row.phone || '',
    city: row.city || '',
    state: row.state || '',
    carrier_class: row.carrier_class || '',
    usdot: row.usdot || '',
    truck_count: row.truck_count || '',
    trailer_count: row.trailer_count || '',
    lead_status: row.lead_status || 'new',
    notes: row.notes || '',
  })

  open.value = true
}

function editRowById(id) {
  const row = rows.value.find((item) => Number(item.id) === Number(id))
  if (row) {
    editRow(row)
  }
}

function closeModal() {
  open.value = false
}

async function saveRow() {
  saving.value = true
  err.value = ''

  try {
    await saveLead(form, form.id)
    open.value = false
    resetForm()
    await loadRows()
  } catch (e) {
    err.value = e?.response?.data?.message || e?.message || 'Failed to save lead'
  } finally {
    saving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  err.value = ''

  try {
    await deleteLead(form.id)
    open.value = false
    resetForm()
    await loadRows()
  } catch (e) {
    err.value = e?.response?.data?.message || e?.message || 'Failed to delete lead'
  } finally {
    deleting.value = false
  }
}

async function convertRow(id) {
  err.value = ''

  try {
    await convertLeadToCarrier(id)
    await loadRows()
  } catch (e) {
    err.value = e?.response?.data?.message || e?.message || 'Failed to convert lead'
  }
}

function handleTableClick(event) {
  const editBtn = event.target.closest('.lead-edit-link')
  if (editBtn) {
    event.preventDefault()
    editRowById(editBtn.getAttribute('data-id'))
    return
  }

  const convertBtn = event.target.closest('.lead-convert-link')
  if (convertBtn) {
    event.preventDefault()
    convertRow(convertBtn.getAttribute('data-id'))
  }
}

watch(q, () => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    loadRows()
  }, 250)
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

.leads-page .top-card {
  padding-top: 6px;
  padding-bottom: 6px;
}

.leads-page .dt-container {
  padding: 0;
}

.leads-page .dt-container .dt-layout-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 0;
  padding: 2px 8px;
}

.leads-page .dt-container .dt-layout-row:first-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.leads-page .dt-container .dt-layout-row:last-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.leads-page .dt-container .dt-layout-cell {
  margin: 0;
}

.leads-page .dt-container .dt-length,
.leads-page .dt-container .dt-info,
.leads-page .dt-container .dt-paging {
  font-size: 12px;
  color: #475569;
}

.leads-page .dt-container .dt-length label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  font-size: 12px;
  color: #475569;
  margin: 0;
}

.leads-page .dt-container .dt-length select {
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

.leads-page .dt-container .dt-info {
  white-space: nowrap;
}

.leads-page .dt-container .dt-paging {
  white-space: nowrap;
}

.leads-page .dt-container .dt-paging .dt-paging-button {
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

.leads-page .dt-container .dt-paging .dt-paging-button.current,
.leads-page .dt-container .dt-paging .dt-paging-button.current:hover {
  border-color: #0f172a !important;
  background: #0f172a !important;
  color: #fff !important;
}

.leads-page .dt-container .dt-paging .dt-paging-button:hover {
  background: #f8fafc !important;
  color: #0f172a !important;
}

.leads-page .dt-scroll-head table.dataTable,
.leads-page .dt-scroll-body table.dataTable,
.leads-page table.dataTable {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.leads-page table.dataTable thead th {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  color: #64748b !important;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0 !important;
}

.leads-page table.dataTable tbody td {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 2px !important;
  padding-bottom: 2px !important;
  vertical-align: middle;
  line-height: 1.05rem;
}

.leads-page table.dataTable tbody tr:hover {
  background: #f8fafc;
}

.leads-page .lead-edit-link {
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

.leads-page .lead-edit-link:hover {
  text-decoration: underline;
}

.leads-page .lead-status-cell {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: nowrap;
  white-space: nowrap;
  line-height: 1;
}

.leads-page .lead-convert-link {
  border: 0;
  background: transparent;
  padding: 0;
  color: #0369a1;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.leads-page .lead-convert-link:hover {
  text-decoration: underline;
}

.leads-page .status-badge {
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

.leads-page .status-badge--new {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.leads-page .status-badge--converted {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.leads-page .status-badge--contacted {
  background: #fff7ed;
  border-color: #fed7aa;
  color: #c2410c;
}

.leads-page .status-badge--qualified {
  background: #faf5ff;
  border-color: #e9d5ff;
  color: #7e22ce;
}

.leads-page .status-badge--default {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

@media (max-width: 767px) {
  .leads-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>