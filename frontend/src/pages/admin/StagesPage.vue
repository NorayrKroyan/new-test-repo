<template>
  <AdminLayout>
    <div class="stages-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Stages</h1>
          </div>

          <div class="grid w-full gap-2 sm:grid-cols-[minmax(220px,1fr)_auto_auto] xl:max-w-[760px]">
            <input
                v-model="q"
                class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search stage"
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

      <div
          v-if="successMessage"
          class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 shadow-sm"
      >
        {{ successMessage }}
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
              {{ row.stage_name || '—' }}
            </button>

            <span
                class="stage-badge"
                :class="Number(row.stage_order || 0) > 0 && Number(row.stage_order || 0) < 10 ? 'stage-badge--on' : 'stage-badge--off'"
            >
              {{ Number(row.stage_order || 0) > 0 && Number(row.stage_order || 0) < 10 ? 'Funnel' : 'No Funnel' }}
            </span>
          </div>

          <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
            <div><span class="font-medium">Group:</span> {{ row.stage_group || '—' }}</div>
            <div><span class="font-medium">Order:</span> {{ row.stage_order || '—' }}</div>
          </div>
        </div>

        <div
            v-if="!loading && !rows.length"
            class="rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-sm"
        >
          No stages found.
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
            class="display nowrap compact stripe row-border hover stages-datatable w-full"
            :data="rows"
            :columns="columns"
            :options="options"
        />
      </div>

      <StageModal
          v-if="open"
          :key="modalKey"
          :open="open"
          :saving="saving"
          :deleting="deleting"
          :form="form"
          :stage-groups="stageGroups"
          :error="modalErr"
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
import StageModal from '../../components/admin/StageModal.vue'
import { deleteStage, fetchStages, saveStage } from '../../api/admin'

DataTable.use(DataTablesCore)
DataTable.use(Responsive)
DataTable.use(FixedHeader)

const q = ref('')
const rows = ref([])
const stageGroups = ref([])
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)
const loading = ref(false)
const err = ref('')
const modalErr = ref('')
const successMessage = ref('')
const tableWrap = ref(null)

const modalKey = ref(0)

const mobilePage = ref(1)
const mobilePageSize = ref(10)

let searchTimer = null
let loadSeq = 0

const form = reactive({
  id: null,
  stage_name: '',
  stage_group: '',
  stage_order: '',
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
    stage_name: '',
    stage_group: '',
    stage_order: '',
  })
}

function clearMessages() {
  err.value = ''
  modalErr.value = ''
  successMessage.value = ''
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

function renderFunnelBadge(order) {
  const isOn = Number(order || 0) > 0 && Number(order || 0) < 10
  return `<span class="stage-badge ${isOn ? 'stage-badge--on' : 'stage-badge--off'}">${isOn ? 'Funnel' : 'No Funnel'}</span>`
}

const columns = [
  {
    title: 'Stage',
    data: null,
    render: (_data, type, row) => {
      const name = displayValue(row.stage_name)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(name)
      }

      return `
        <button
          type="button"
          class="stage-edit-link"
          data-id="${row.id}"
        >
          ${esc(name)}
        </button>
      `
    },
  },
  {
    title: 'Group',
    data: 'stage_group',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Order',
    data: 'stage_order',
    render: (data, type) => {
      const value = displayValue(data)

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(value)
      }

      return esc(value)
    },
  },
  {
    title: 'Funnel',
    data: 'stage_order',
    render: (data, type) => {
      if (type === 'sort' || type === 'type' || type === 'filter') {
        return Number(data || 0) > 0 && Number(data || 0) < 10 ? 'Funnel' : 'No Funnel'
      }

      return renderFunnelBadge(data)
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
    emptyTable: 'No stages found',
    zeroRecords: 'No stages found',
  },
}

function buildStagePayload() {
  return {
    id: form.id,
    stage_name: form.stage_name || '',
    stage_group: form.stage_group || '',
    stage_order: form.stage_order === '' ? null : Number(form.stage_order),
  }
}

async function loadRows() {
  const seq = ++loadSeq
  loading.value = true
  err.value = ''

  try {
    const data = await fetchStages({ q: q.value })

    if (seq !== loadSeq) return

    rows.value = Array.isArray(data?.data) ? [...data.data] : []
    stageGroups.value = Array.isArray(data?.meta?.groups) ? [...data.meta.groups] : []
    mobilePage.value = 1
  } catch (e) {
    if (seq !== loadSeq) return

    err.value = extractErrorMessage(e)
    rows.value = []
    stageGroups.value = []
    mobilePage.value = 1
  } finally {
    if (seq === loadSeq) {
      loading.value = false
    }
  }
}

function openCreate() {
  clearMessages()
  resetForm()
  modalKey.value += 1
  open.value = true
}

function editRow(row) {
  clearMessages()

  Object.assign(form, {
    id: row.id,
    stage_name: row.stage_name || '',
    stage_group: row.stage_group || '',
    stage_order: row.stage_order || '',
  })

  modalKey.value += 1
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
  modalErr.value = ''
}

async function saveRow() {
  saving.value = true
  modalErr.value = ''
  err.value = ''

  try {
    await saveStage(buildStagePayload(), form.id)
    open.value = false
    resetForm()
    modalErr.value = ''
    successMessage.value = 'Stage saved.'
    await loadRows()
  } catch (e) {
    modalErr.value = extractErrorMessage(e)
  } finally {
    saving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  modalErr.value = ''
  err.value = ''

  try {
    await deleteStage(form.id)
    open.value = false
    resetForm()
    modalErr.value = ''
    successMessage.value = 'Stage deleted.'
    await loadRows()
  } catch (e) {
    modalErr.value = extractErrorMessage(e)
  } finally {
    deleting.value = false
  }
}

function handleTableClick(event) {
  const editBtn = event.target.closest('.stage-edit-link')
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

.stages-page .top-card {
  padding-top: 6px;
  padding-bottom: 6px;
}

.stages-page .dt-container {
  padding: 0;
}

.stages-page .dt-container .dt-layout-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin: 0;
  padding: 2px 8px;
}

.stages-page .dt-container .dt-layout-row:first-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.stages-page .dt-container .dt-layout-row:last-child {
  padding-top: 2px;
  padding-bottom: 2px;
}

.stages-page .dt-container .dt-layout-cell {
  margin: 0;
}

.stages-page .dt-container .dt-length,
.stages-page .dt-container .dt-info,
.stages-page .dt-container .dt-paging {
  font-size: 12px;
  color: #475569;
}

.stages-page .dt-container .dt-length label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  font-size: 12px;
  color: #475569;
  margin: 0;
}

.stages-page .dt-container .dt-length select {
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

.stages-page .dt-container .dt-info {
  white-space: nowrap;
}

.stages-page .dt-container .dt-paging {
  white-space: nowrap;
}

.stages-page .dt-container .dt-paging .dt-paging-button {
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

.stages-page .dt-container .dt-paging .dt-paging-button.current,
.stages-page .dt-container .dt-paging .dt-paging-button.current:hover {
  border-color: #0f172a !important;
  background: #0f172a !important;
  color: #fff !important;
}

.stages-page .dt-container .dt-paging .dt-paging-button:hover {
  background: #f8fafc !important;
  color: #0f172a !important;
}

.stages-page .dt-scroll-head table.dataTable,
.stages-page .dt-scroll-body table.dataTable,
.stages-page table.dataTable {
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.stages-page table.dataTable thead th {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 4px !important;
  padding-bottom: 4px !important;
  color: #64748b !important;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0 !important;
}

.stages-page table.dataTable tbody td {
  white-space: nowrap;
  font-size: 13px;
  padding-top: 2px !important;
  padding-bottom: 2px !important;
  vertical-align: middle;
  line-height: 1.05rem;
}

.stages-page table.dataTable tbody tr:hover {
  background: #f8fafc;
}

.stages-page .stage-edit-link {
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

.stages-page .stage-edit-link:hover {
  text-decoration: underline;
}

.stages-page .stage-badge {
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

.stages-page .stage-badge--on {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.stages-page .stage-badge--off {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

@media (max-width: 767px) {
  .stages-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>