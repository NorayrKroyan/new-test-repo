<template>
  <AdminLayout>
    <div class="customers-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Customers</h1>
          </div>

          <div class="grid w-full gap-2 sm:grid-cols-[minmax(260px,1fr)_auto] xl:max-w-[640px]">
            <input
                v-model="q"
                class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search customer"
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
              {{ row.company_name || row.contact_name || '—' }}
            </button>

            <span
                class="status-badge"
                :class="statusBadgeClass(row.status)"
            >
              {{ displayStatusLabel(row.status) }}
            </span>
          </div>

          <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
            <div><span class="font-medium">Contact:</span> {{ row.contact_name || '—' }}</div>
            <div class="break-all"><span class="font-medium">Email:</span> {{ row.email || '—' }}</div>
            <div><span class="font-medium">Phone:</span> {{ row.phone || '—' }}</div>
            <div><span class="font-medium">Location:</span> {{ row.city || '—' }}, {{ row.state || '—' }}</div>
          </div>
        </div>

        <div
            v-if="!loading && !rows.length"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-sm"
        >
          No customers found.
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
            class="display nowrap compact stripe row-border hover customers-datatable w-full"
            :data="rows"
            :columns="columns"
            :options="options"
        />
      </div>

      <CustomerModal
          v-if="open"
          :key="customerModalKey"
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
import CustomerModal from '../../components/admin/CustomerModal.vue'
import { deleteCustomer, fetchCustomers, saveCustomer } from '../../api/admin'

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
const customerModalKey = ref(0)

const mobilePage = ref(1)
const mobilePageSize = ref(10)

let searchTimer = null
let loadSeq = 0

const form = reactive({
  id: null,
  company_name: '',
  contact_name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  status: 'pending_review',
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
    company_name: '',
    contact_name: '',
    email: '',
    password: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    status: 'pending_review',
    notes: '',
  })
}

function extractErrorMessage(e) {
  const errors = e?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    const firstValue = firstKey ? errors[firstKey] : null
    if (Array.isArray(firstValue) && firstValue.length) {
      return String(firstValue[0])
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

  if (key === 'pending_review') {
    return {
      label: 'Pending Review',
      className: 'status-badge--pending',
    }
  }

  if (key === 'active') {
    return {
      label: 'Active',
      className: 'status-badge--active',
    }
  }

  if (key === 'inactive') {
    return {
      label: 'Inactive',
      className: 'status-badge--inactive',
    }
  }

  if (!key) {
    return {
      label: 'Pending Review',
      className: 'status-badge--pending',
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
    title: 'Company',
    data: null,
    render: (_data, type, row) => {
      const value = displayValue(row.company_name || row.contact_name)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return `
        <button
          type="button"
          class="customer-edit-link"
          data-id="${row.id}"
        >
          ${esc(value)}
        </button>
      `
    },
  },
  {
    title: 'Contact',
    data: 'contact_name',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
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
    title: 'Location',
    data: null,
    render: (_data, type, row) => {
      const value = `${displayValue(row.city)}, ${displayValue(row.state)}`

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(`${row.city || ''} ${row.state || ''}`)
      }

      return esc(value)
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
    emptyTable: 'No customers found',
    zeroRecords: 'No customers found',
  },
}

async function loadRows() {
  const seq = ++loadSeq
  loading.value = true
  err.value = ''

  try {
    const data = await fetchCustomers({ q: q.value })

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
  customerModalKey.value += 1
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    company_name: row.company_name || '',
    contact_name: row.contact_name || '',
    email: row.email || '',
    password: '',
    phone: row.phone || '',
    address: row.address || '',
    city: row.city || '',
    state: row.state || '',
    status: row.status || 'pending_review',
    notes: row.notes || '',
  })

  customerModalKey.value += 1
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
    await saveCustomer(form, form.id)
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
    await deleteCustomer(form.id)
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
  const editBtn = event.target.closest('.customer-edit-link')
  if (editBtn) {
    event.preventDefault()
    editRowById(editBtn.getAttribute('data-id'))
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

.customers-page .top-card {
  padding-top: 6px;
  padding-bottom: 6px;
}

.customers-page .dt-container {
  padding: 0;
}

.customers-page .dt-container .dt-layout-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 0;
  padding: 2px 8px;
}

.customers-page .dt-container .dt-layout-row:first-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.customers-page .dt-container .dt-layout-row:last-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.customers-page .dt-container .dt-layout-cell {
  margin: 0;
}

.customers-page .dt-container .dt-length,
.customers-page .dt-container .dt-info,
.customers-page .dt-container .dt-paging {
  font-size: 12px;
  color: #475569;
}

.customers-page .dt-container .dt-length label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  font-size: 12px;
  color: #475569;
  margin: 0;
}

.customers-page .dt-container .dt-length select {
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

.customers-page .dt-container .dt-info {
  white-space: nowrap;
}

.customers-page .dt-container .dt-paging {
  white-space: nowrap;
}

.customers-page .dt-container .dt-paging .dt-paging-button {
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

.customers-page .dt-container .dt-paging .dt-paging-button.current,
.customers-page .dt-container .dt-paging .dt-paging-button.current:hover {
  border-color: #0f172a !important;
  background: #0f172a !important;
  color: #fff !important;
}

.customers-page .dt-container .dt-paging .dt-paging-button:hover {
  background: #f8fafc !important;
  color: #0f172a !important;
}

.customers-page .dt-scroll-head table.dataTable,
.customers-page .dt-scroll-body table.dataTable,
.customers-page table.dataTable {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.customers-page table.dataTable thead th {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  color: #64748b !important;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0 !important;
}

.customers-page table.dataTable tbody td {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 2px !important;
  padding-bottom: 2px !important;
  vertical-align: middle;
  line-height: 1.05rem;
}

.customers-page table.dataTable tbody tr:hover {
  background: #f8fafc;
}

.customers-page .customer-edit-link {
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

.customers-page .customer-edit-link:hover {
  text-decoration: underline;
}

.customers-page .status-badge {
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

.customers-page .status-badge--pending {
  background: #fff7ed;
  border-color: #fed7aa;
  color: #c2410c;
}

.customers-page .status-badge--active {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.customers-page .status-badge--inactive {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.customers-page .status-badge--default {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

@media (max-width: 767px) {
  .customers-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>