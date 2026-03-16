<template>
  <AdminLayout>
    <div class="leads-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-1.5 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Leads</h1>
          </div>

          <div class="grid w-full gap-2 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_150px_200px_200px_130px_auto_auto] xl:max-w-full">
            <input
                v-model="q"
                class="h-10 min-w-0 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-400"
                placeholder="Search lead"
            />

            <select
                v-model="scope"
                class="h-10 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
            >
              <option value="active">Active</option>
              <option value="duplicates">Duplicates</option>
              <option value="all">All</option>
            </select>

            <select
                v-model="adName"
                class="h-10 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
            >
              <option value="">All Ad Names</option>
              <option v-for="name in adNames" :key="name" :value="name">
                {{ name }}
              </option>
            </select>

            <select
                v-model="stageId"
                class="h-10 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
            >
              <option value="">All Stages</option>
              <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">
                {{ stage.stage_name }} / {{ stage.stage_group }} / {{ stage.stage_order }}
              </option>
            </select>

            <div class="funnel-toggle-wrap h-10 rounded-xl border border-slate-300 bg-white px-3">
              <label for="lead-funnel-toggle" class="funnel-toggle-label">Funnel</label>

              <label for="lead-funnel-toggle" class="funnel-toggle-native">
                <input
                    id="lead-funnel-toggle"
                    v-model="funnelEnabled"
                    type="checkbox"
                    class="funnel-toggle-input"
                />
                <span class="funnel-toggle-slider">
                  <span class="funnel-toggle-handle"></span>
                </span>
              </label>
            </div>

            <button
                class="inline-flex h-10 min-w-[150px] items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                :disabled="deduping"
                @click="runAutoDedupNow"
            >
              {{ deduping ? 'Deduping...' : 'Auto Dedup' }}
            </button>
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

      <div
          v-if="funnelEnabled && showFunnel"
          class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
      >
        <div class="mb-2 text-sm font-semibold text-slate-900">Lead Funnel</div>

        <iframe
            :key="funnelChartFrameKey"
            :src="funnelChartUrl"
            title="Lead Funnel"
            class="lead-funnel-frame"
            loading="lazy"
        />
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
            <div><span class="font-medium">Ad Name:</span> {{ row.ad_name || '—' }}</div>
            <div><span class="font-medium">Platform:</span> {{ row.platform || '—' }}</div>
            <div><span class="font-medium">City:</span> {{ row.city || '—' }}</div>
            <div><span class="font-medium">Insurance:</span> {{ row.insurance_answer || '—' }}</div>
            <div><span class="font-medium">Stage:</span> {{ displayStageLabel(row) }}</div>

            <div v-if="row.duplicate_of_lead_id">
              <span class="font-medium">Duplicate Of:</span>
              #{{ row.duplicate_of_lead_id }}
              <span v-if="row.duplicate_basis">({{ duplicateBasisLabel(row.duplicate_basis) }})</span>
            </div>

            <div v-if="Number(row.duplicates_count || 0) > 0">
              <span class="font-medium">Duplicates:</span>
              {{ row.duplicates_count }}
            </div>
          </div>

          <div class="mt-2 flex flex-wrap gap-2">
            <button
                v-if="canConvert(row)"
                class="rounded-xl border border-sky-300 px-3 py-1.5 text-sm font-medium text-sky-700 hover:bg-sky-50"
                @click="convertRow(row.id)"
            >
              Convert
            </button>

            <button
                v-if="canMarkDuplicate(row)"
                class="rounded-xl border border-amber-300 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50"
                @click="openDuplicateModal(row)"
            >
              Mark Dup
            </button>

            <button
                v-if="canUnmarkDuplicate(row)"
                class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="unmarkDuplicateRow(row.id)"
            >
              Unmark
            </button>

            <button
                v-if="canMerge(row)"
                class="rounded-xl border border-violet-300 px-3 py-1.5 text-sm font-medium text-violet-700 hover:bg-violet-50"
                @click="openMerge(row.id)"
            >
              Merge
            </button>
          </div>
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
          v-if="open"
          :key="leadModalKey"
          :open="open"
          :saving="saving"
          :deleting="deleting"
          :form="form"
          :stages="stages"
          @close="closeModal"
          @save="saveRow"
          @delete="deleteCurrent"
      />

      <LeadDuplicateModal
          v-if="duplicateOpen"
          :key="duplicateModalKey"
          :open="duplicateOpen"
          :saving="duplicateSaving"
          :lead="duplicateLead"
          :form="duplicateForm"
          :error="duplicateErr"
          @close="closeDuplicateModal"
          @save="saveDuplicateModal"
      />

      <LeadMergeModal
          v-if="mergeOpen"
          :key="mergeModalKey"
          :open="mergeOpen"
          :preview="mergePreview"
          :loading="mergeLoading"
          :saving="mergeSaving"
          @close="closeMerge"
          @save="saveMerge"
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
import LeadDuplicateModal from '../../components/admin/LeadDuplicateModal.vue'
import LeadMergeModal from '../../components/admin/LeadMergeModal.vue'
import {
  convertLeadToCarrier,
  deleteLead,
  fetchLeadAdNames,
  fetchLeadFunnelSummary,
  fetchLeadMergePreview,
  fetchLeads,
  fetchStages,
  markLeadDuplicate,
  mergeLeadGroup,
  runLeadAutoDedup,
  saveLead,
  unmarkLeadDuplicate,
} from '../../api/admin'

DataTable.use(DataTablesCore)
DataTable.use(Responsive)
DataTable.use(FixedHeader)

const q = ref('')
const scope = ref('active')
const adName = ref('')
const adNames = ref([])
const stageId = ref('')
const rows = ref([])
const stages = ref([])
const funnelChartFrameKey = ref(0)
const showFunnel = ref(false)
const funnelEnabled = ref(true)
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)
const loading = ref(false)
const deduping = ref(false)
const err = ref('')
const successMessage = ref('')
const tableWrap = ref(null)

const leadModalKey = ref(0)
const duplicateModalKey = ref(0)
const mergeModalKey = ref(0)

const duplicateOpen = ref(false)
const duplicateSaving = ref(false)
const duplicateLead = ref(null)
const duplicateErr = ref('')
const duplicateForm = reactive({
  master_lead_id: '',
})

const mergeOpen = ref(false)
const mergeLoading = ref(false)
const mergeSaving = ref(false)
const mergeRowId = ref(null)
const mergePreview = ref(null)

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
  lead_stage_id: '',
  notes: '',
  duplicate_of_lead_id: null,
  duplicate_basis: '',
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

const funnelChartUrl = computed(() => {
  const params = new URLSearchParams()

  if (q.value) params.set('q', q.value)
  if (scope.value) params.set('scope', scope.value)
  if (adName.value) params.set('ad_name', adName.value)
  if (stageId.value) params.set('stage_id', stageId.value)

  params.set('_frame', String(funnelChartFrameKey.value))

  return `/api/admin/leads/funnel-chart?${params.toString()}`
})

function buildFunnelParams() {
  return {
    q: q.value || undefined,
    scope: scope.value || undefined,
    ad_name: adName.value || undefined,
    stage_id: stageId.value || undefined,
  }
}

async function loadFunnelVisibility() {
  if (!adName.value) {
    return false
  }

  const data = await fetchLeadFunnelSummary(buildFunnelParams())

  return Array.isArray(data?.data) && data.data.length > 0
}

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
    lead_stage_id: '',
    notes: '',
    duplicate_of_lead_id: null,
    duplicate_basis: '',
  })
}

function clearMessages() {
  err.value = ''
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

function duplicateBasisLabel(basis) {
  const key = String(basis ?? '').trim().toLowerCase()

  if (key === 'phone') return 'matched by phone'
  if (key === 'email') return 'matched by email'
  if (key === 'manual') return 'manual'
  if (key === 'merged') return 'merged'

  return key ? toTitleWords(key) : 'duplicate'
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

  if (key === 'duplicate') {
    return {
      label: 'Duplicate',
      className: 'status-badge--duplicate',
    }
  }

  if (key === 'merged') {
    return {
      label: 'Merged',
      className: 'status-badge--merged',
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

function displayStageLabel(row) {
  if (row?.stage?.stage_name) {
    return `${row.stage.stage_name}${row.stage.stage_group ? ` (${row.stage.stage_group})` : ''}`
  }

  return '—'
}

function renderStageCell(row, type) {
  const stageName = row?.stage?.stage_name || ''

  if (type === 'sort' || type === 'type' || type === 'filter') {
    return stageName
  }

  if (!stageName) {
    return '—'
  }

  return `
    <div class="lead-stage-cell">
      <div class="lead-stage-name">${esc(stageName)}</div>
    </div>
  `
}

function canConvert(row) {
  return normalizeStatus(row.lead_status) !== 'converted_to_carrier' && !Number(row.duplicate_of_lead_id || 0)
}

function canMerge(row) {
  return Number(row.duplicates_count || 0) > 0 || Number(row.duplicate_of_lead_id || 0) > 0
}

function canMarkDuplicate(row) {
  return !Number(row.duplicate_of_lead_id || 0) && Number(row.duplicates_count || 0) === 0
}

function canUnmarkDuplicate(row) {
  return Number(row.duplicate_of_lead_id || 0) > 0
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
    title: 'Ad Name',
    data: 'ad_name',
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
    title: 'Stage',
    data: null,
    render: (_data, type, row) => renderStageCell(row, type),
  },
  {
    title: 'Status',
    data: null,
    render: (_data, type, row) => {
      const rawStatus = row.lead_status

      if (type === 'sort' || type === 'type' || type === 'filter') {
        return sortValue(displayStatusLabel(rawStatus))
      }

      const actions = []

      if (canConvert(row)) {
        actions.push(`
          <button
            type="button"
            class="lead-action-link lead-convert-link"
            data-id="${row.id}"
            data-action="convert"
          >
            Convert
          </button>
        `)
      }

      if (canMarkDuplicate(row)) {
        actions.push(`
          <button
            type="button"
            class="lead-action-link lead-duplicate-link"
            data-id="${row.id}"
            data-action="mark-duplicate"
          >
            Mark Dup
          </button>
        `)
      }

      if (canUnmarkDuplicate(row)) {
        actions.push(`
          <button
            type="button"
            class="lead-action-link lead-unmark-link"
            data-id="${row.id}"
            data-action="unmark-duplicate"
          >
            Unmark
          </button>
        `)
      }

      if (canMerge(row)) {
        actions.push(`
          <button
            type="button"
            class="lead-action-link lead-merge-link"
            data-id="${row.id}"
            data-action="merge"
          >
            Merge
          </button>
        `)
      }

      return `
        <div class="lead-status-cell">
          ${renderStatusBadge(rawStatus)}
          ${actions.join('')}
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

function buildLeadPayload() {
  return {
    id: form.id,
    source_name: form.source_name || '',
    ad_name: form.ad_name || '',
    platform: form.platform || '',
    source_created_at: form.source_created_at || '',
    lead_date_choice: form.lead_date_choice || '',
    insurance_answer: form.insurance_answer || '',
    full_name: form.full_name || '',
    email: form.email || '',
    phone: form.phone || '',
    city: form.city || '',
    state: form.state || '',
    carrier_class: form.carrier_class || '',
    usdot: form.usdot || '',
    truck_count: form.truck_count === '' ? null : form.truck_count,
    trailer_count: form.trailer_count === '' ? null : form.trailer_count,
    lead_status: form.lead_status || 'new',
    lead_stage_id: form.lead_stage_id === '' ? null : Number(form.lead_stage_id),
    notes: form.notes || '',
  }
}

async function loadAdNames() {
  const data = await fetchLeadAdNames()
  adNames.value = Array.isArray(data?.data) ? [...data.data] : []
}

async function loadStages() {
  let data = await fetchStages({
    group: adName.value || undefined,
  })

  let nextStages = Array.isArray(data?.data) ? [...data.data] : []

  if (adName.value && nextStages.length === 0) {
    data = await fetchStages()
    nextStages = Array.isArray(data?.data) ? [...data.data] : []
  }

  stages.value = nextStages

  if (stageId.value && !nextStages.some((stage) => String(stage.id) === String(stageId.value))) {
    stageId.value = ''
  }
}

async function loadRows() {
  const seq = ++loadSeq
  loading.value = true
  err.value = ''

  try {
    const data = await fetchLeads({
      q: q.value,
      scope: scope.value,
      ad_name: adName.value || undefined,
      stage_id: stageId.value || undefined,
    })

    if (seq !== loadSeq) return

    rows.value = Array.isArray(data?.data) ? [...data.data] : []
    mobilePage.value = 1

    try {
      if (!funnelEnabled.value || !adName.value) {
        showFunnel.value = false
        return
      }

      const hasFunnelData = await loadFunnelVisibility()

      if (seq !== loadSeq) return

      showFunnel.value = hasFunnelData

      if (hasFunnelData) {
        funnelChartFrameKey.value += 1
      }
    } catch (funnelError) {
      if (seq !== loadSeq) return

      showFunnel.value = false
      err.value = extractErrorMessage(funnelError)
    }
  } catch (e) {
    if (seq !== loadSeq) return

    err.value = extractErrorMessage(e)
    rows.value = []
    mobilePage.value = 1
    showFunnel.value = false
  } finally {
    if (seq === loadSeq) {
      loading.value = false
    }
  }
}

function openCreate() {
  clearMessages()
  resetForm()
  leadModalKey.value += 1
  open.value = true
}

function editRow(row) {
  clearMessages()

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
    lead_stage_id: row.lead_stage_id ? String(row.lead_stage_id) : (row.stage?.id ? String(row.stage.id) : ''),
    notes: row.notes || '',
    duplicate_of_lead_id: row.duplicate_of_lead_id || null,
    duplicate_basis: row.duplicate_basis || '',
  })

  leadModalKey.value += 1
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
    await saveLead(buildLeadPayload(), form.id)
    open.value = false
    resetForm()
    successMessage.value = 'Lead saved.'
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
    await deleteLead(form.id)
    open.value = false
    resetForm()
    successMessage.value = 'Lead deleted.'
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  } finally {
    deleting.value = false
  }
}

async function convertRow(id) {
  clearMessages()

  try {
    await convertLeadToCarrier(id)
    successMessage.value = 'Lead converted.'
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  }
}

function openDuplicateModal(row) {
  clearMessages()
  duplicateErr.value = ''
  duplicateLead.value = row
  duplicateForm.master_lead_id = ''
  duplicateModalKey.value += 1
  duplicateOpen.value = true
}

function closeDuplicateModal() {
  duplicateOpen.value = false
  duplicateSaving.value = false
  duplicateLead.value = null
  duplicateForm.master_lead_id = ''
  duplicateErr.value = ''
}

async function saveDuplicateModal() {
  if (!duplicateLead.value) return

  duplicateErr.value = ''
  const masterLeadId = Number(duplicateForm.master_lead_id)

  if (!Number.isInteger(masterLeadId) || masterLeadId <= 0) {
    duplicateErr.value = 'Please enter a valid master lead ID.'
    return
  }

  duplicateSaving.value = true

  try {
    const targetLeadId = duplicateLead.value.id
    await markLeadDuplicate(targetLeadId, { master_lead_id: masterLeadId })
    closeDuplicateModal()
    successMessage.value = `Lead #${targetLeadId} marked as duplicate of #${masterLeadId}.`
    await loadRows()
  } catch (e) {
    duplicateErr.value = extractErrorMessage(e)
  } finally {
    duplicateSaving.value = false
  }
}

async function unmarkDuplicateRow(id) {
  clearMessages()

  try {
    await unmarkLeadDuplicate(id)
    successMessage.value = `Lead #${id} unmarked.`
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  }
}

async function runAutoDedupNow() {
  clearMessages()
  deduping.value = true

  try {
    const data = await runLeadAutoDedup({ match_by: 'any' })
    const marked = Number(data?.summary?.duplicates_marked || 0)
    successMessage.value = marked > 0 ? `Marked ${marked} duplicate lead${marked === 1 ? '' : 's'}.` : 'No exact duplicates found.'
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  } finally {
    deduping.value = false
  }
}

async function openMerge(id) {
  clearMessages()
  mergeLoading.value = true
  mergeSaving.value = false
  mergeRowId.value = Number(id)
  mergePreview.value = null
  mergeModalKey.value += 1
  mergeOpen.value = true

  try {
    const data = await fetchLeadMergePreview(id)
    mergePreview.value = data?.data || null
  } catch (e) {
    mergeOpen.value = false
    mergeRowId.value = null
    err.value = extractErrorMessage(e)
  } finally {
    mergeLoading.value = false
  }
}

function closeMerge() {
  mergeOpen.value = false
  mergeLoading.value = false
  mergeSaving.value = false
  mergeRowId.value = null
  mergePreview.value = null
}

async function saveMerge(payload) {
  if (!mergeRowId.value) return

  clearMessages()
  mergeSaving.value = true

  try {
    await mergeLeadGroup(mergeRowId.value, payload)
    closeMerge()
    successMessage.value = 'Duplicate group merged into one survivor.'
    await loadRows()
  } catch (e) {
    err.value = extractErrorMessage(e)
  } finally {
    mergeSaving.value = false
  }
}

function handleTableClick(event) {
  const editBtn = event.target.closest('.lead-edit-link')
  if (editBtn) {
    event.preventDefault()
    editRowById(editBtn.getAttribute('data-id'))
    return
  }

  const actionBtn = event.target.closest('.lead-action-link')
  if (!actionBtn) {
    return
  }

  event.preventDefault()

  const rowId = Number(actionBtn.getAttribute('data-id'))
  const action = actionBtn.getAttribute('data-action')
  const row = rows.value.find((item) => Number(item.id) === rowId)

  if (!row) {
    return
  }

  if (action === 'convert') {
    convertRow(row.id)
    return
  }

  if (action === 'mark-duplicate') {
    openDuplicateModal(row)
    return
  }

  if (action === 'unmark-duplicate') {
    unmarkDuplicateRow(row.id)
    return
  }

  if (action === 'merge') {
    openMerge(row.id)
  }
}

watch([q, scope, adName, stageId], () => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(async () => {
    await loadStages()
    await loadRows()
  }, 250)
})

watch(funnelEnabled, async (enabled) => {
  if (!enabled) {
    return
  }

  await loadRows()
})

onMounted(async () => {
  await loadAdNames()
  await loadStages()
  await loadRows()

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

.leads-page .lead-action-link {
  border: 0;
  background: transparent;
  padding: 0;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.leads-page .lead-convert-link {
  color: #0369a1;
}

.leads-page .lead-duplicate-link {
  color: #b45309;
}

.leads-page .lead-unmark-link {
  color: #475569;
}

.leads-page .lead-merge-link {
  color: #7c3aed;
}

.leads-page .lead-action-link:hover {
  text-decoration: underline;
}

.leads-page .lead-stage-cell {
  display: inline-flex;
  flex-direction: column;
  gap: 1px;
  line-height: 1.05rem;
  white-space: nowrap;
}

.leads-page .lead-stage-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
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

.leads-page .status-badge--duplicate {
  background: #fffbeb;
  border-color: #fcd34d;
  color: #b45309;
}

.leads-page .status-badge--merged {
  background: #f5f3ff;
  border-color: #c4b5fd;
  color: #7c3aed;
}

.leads-page .status-badge--default {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.leads-page .funnel-toggle-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.leads-page .funnel-toggle-label {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
}

.leads-page .funnel-toggle-native {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 42px;
  min-width: 42px;
  height: 24px;
  flex: 0 0 42px;
  cursor: pointer;
}

.leads-page .funnel-toggle-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  cursor: pointer;
  z-index: 3;
}

.leads-page .funnel-toggle-slider {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: #e2e8f0;
  border: 1px solid #cbd5e1;
  transition: all 0.2s ease;
}

.leads-page .funnel-toggle-handle {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
  transition: transform 0.2s ease;
}

.leads-page .funnel-toggle-input:checked + .funnel-toggle-slider {
  background: #0f172a;
  border-color: #0f172a;
}

.leads-page .funnel-toggle-input:checked + .funnel-toggle-slider .funnel-toggle-handle {
  transform: translateX(18px);
}

.leads-page .funnel-toggle-input:focus-visible + .funnel-toggle-slider {
  box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.12);
}

.leads-page .lead-funnel-frame {
  display: block;
  width: 100%;
  min-height: 320px;
  border: 0;
  background: #ffffff;
}

@media (max-width: 1279px) {
  .leads-page .top-card .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .leads-page .dt-container .dt-layout-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>