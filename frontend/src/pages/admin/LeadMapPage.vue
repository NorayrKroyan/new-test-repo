<template>
  <AdminLayout>
    <div class="lead-map-page space-y-1">
      <div class="top-card rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <h1 class="text-2xl font-semibold leading-tight text-slate-900">Lead Map</h1>
          </div>

          <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <button
                class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                :disabled="loading || geocoding"
                @click="reloadAll"
            >
              Reload
            </button>

            <button
                class="h-9 rounded-xl border border-slate-900 bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
                :disabled="!adName || loading || geocoding"
                @click="geocodeMissing"
            >
              {{ geocoding ? 'Geocoding...' : 'Geocode Missing' }}
            </button>
          </div>
        </div>

        <div class="mt-2 grid w-full gap-2 sm:grid-cols-[minmax(260px,1fr)_220px_160px]">
          <select
              v-model="adName"
              class="h-9 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
          >
            <option value="">Choose Ad</option>
            <option v-for="name in adNames" :key="name" :value="name">
              {{ name }}
            </option>
          </select>

          <select
              v-model="stageId"
              class="h-9 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
              :disabled="!adName"
          >
            <option value="">All Stages</option>
            <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">
              {{ stageLabel(stage) }}
            </option>
          </select>

          <select
              v-model="scope"
              class="h-9 min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
          >
            <option value="active">Active</option>
            <option value="duplicates">Duplicates</option>
            <option value="all">All</option>
          </select>
        </div>
      </div>

      <div
          v-if="err"
          class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
      >
        {{ err }}
      </div>

      <div
          v-if="successMessage"
          class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700"
      >
        {{ successMessage }}
      </div>

      <div class="grid gap-1 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
          <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Total Leads</div>
          <div class="mt-1 text-[22px] font-semibold leading-none text-slate-900">{{ counts.total }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
          <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Mapped Leads</div>
          <div class="mt-1 text-[22px] font-semibold leading-none text-slate-900">{{ counts.mapped }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
          <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Unmapped Leads</div>
          <div class="mt-1 text-[22px] font-semibold leading-none text-slate-900">{{ counts.unmapped }}</div>
        </div>
      </div>

      <div class="grid gap-1 xl:grid-cols-[minmax(0,1.7fr)_390px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
          <div class="mb-1 flex items-center justify-between gap-2">
            <div>
              <div class="text-lg font-semibold leading-tight text-slate-900">Map View</div>
              <div class="text-xs text-slate-500">
                Pins are color-coded by stage. Hover for quick info, click for full details.
              </div>
            </div>

            <div
                v-if="loading"
                class="rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500"
            >
              Loading...
            </div>
          </div>

          <div
              v-if="!adName"
              class="flex h-[56vh] min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 text-center text-sm text-slate-500"
          >
            Choose an ad to load the campaign map.
          </div>

          <div
              v-else
              ref="mapEl"
              class="h-[56vh] min-h-[420px] w-full rounded-2xl border border-slate-200 bg-slate-100"
          />

          <div
              v-if="adName && !loading && !displayRows.length"
              class="mt-2 rounded-xl border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-500"
          >
            No mapped leads yet for this ad. Click <span class="font-medium">Geocode Missing</span> to resolve city-based pins.
          </div>
        </div>

        <div class="space-y-1">
          <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
            <div class="mb-2 flex items-center justify-between gap-2">
              <div class="text-lg font-semibold leading-tight text-slate-900">Lead Details</div>

              <div
                  v-if="selectedLead"
                  class="rounded-full border px-2 py-0.5 text-[11px] font-medium"
                  :style="stageBadgeStyle(selectedLead)"
              >
                {{ displayStageName(selectedLead) }}
              </div>
            </div>

            <template v-if="selectedLead">
              <div class="text-sm font-semibold text-slate-900">
                {{ selectedLead.full_name || `Lead #${selectedLead.id}` }}
              </div>

              <div class="mt-0.5 text-xs text-slate-500">
                Lead #{{ selectedLead.id }}
                <span v-if="selectedLead.phone"> • {{ selectedLead.phone }}</span>
              </div>

              <div class="mt-2 grid grid-cols-[88px_1fr] gap-x-3 gap-y-1.5 text-sm leading-5">
                <div class="font-medium text-slate-900">Campaign</div>
                <div class="min-w-0 truncate text-slate-700">{{ selectedLead.ad_name || '—' }}</div>

                <div class="font-medium text-slate-900">Platform</div>
                <div class="min-w-0 truncate text-slate-700">{{ selectedLead.platform || '—' }}</div>

                <div class="font-medium text-slate-900">Resolved</div>
                <div class="min-w-0 truncate text-slate-700">{{ locationLabel(selectedLead) }}</div>

                <template v-if="selectedLead.email">
                  <div class="font-medium text-slate-900">Email</div>
                  <div class="min-w-0 break-all text-slate-700">{{ selectedLead.email }}</div>
                </template>

                <template v-if="selectedLead.formatted_address">
                  <div class="font-medium text-slate-900">Address</div>
                  <div class="min-w-0 text-slate-700">{{ selectedLead.formatted_address }}</div>
                </template>
              </div>

              <div class="mt-2 flex flex-wrap gap-2">
                <button
                    class="rounded-lg border border-slate-900 bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800"
                    @click="openSharedLeadModal(selectedLead)"
                >
                  Open Lead Modal
                </button>

                <button
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                    :disabled="geocoding"
                    @click="geocodeSingleLead(selectedLead.id)"
                >
                  Re-geocode
                </button>
              </div>
            </template>

            <div v-else class="text-sm text-slate-500">
              Click a lead pin to load lead details.
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
            <div class="mb-2 flex items-center justify-between gap-2">
              <div class="text-lg font-semibold leading-tight text-slate-900">Unmapped Leads</div>
              <div
                  class="rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-500"
              >
                {{ unresolvedRows.length }}
              </div>
            </div>

            <div
                v-if="!unresolvedRows.length"
                class="text-sm text-slate-500"
            >
              No unresolved leads for the current filter.
            </div>

            <div
                v-else
                class="max-h-[44vh] space-y-1 overflow-y-auto pr-1"
            >
              <div
                  v-for="row in unresolvedRows"
                  :key="row.id"
                  class="rounded-xl border border-slate-200 px-2.5 py-2"
              >
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-slate-900">
                      {{ row.full_name || `Lead #${row.id}` }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500">
                      Lead #{{ row.id }}
                      <span v-if="row.phone"> • {{ row.phone }}</span>
                    </div>
                  </div>

                  <div
                      class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-medium"
                      :style="stageBadgeStyle(row)"
                  >
                    {{ displayStageName(row) }}
                  </div>
                </div>

                <div
                    v-if="row.map_last_error"
                    class="mt-1 text-xs leading-4 text-slate-600"
                >
                  Error: {{ row.map_last_error }}
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                      class="rounded-lg border border-slate-900 bg-slate-900 px-2.5 py-1 text-xs font-medium text-white hover:bg-slate-800"
                      @click="openSharedLeadModal(row)"
                  >
                    Open Lead Modal
                  </button>

                  <button
                      class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                      :disabled="geocoding"
                      @click="geocodeSingleLead(row.id)"
                  >
                    Re-geocode
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <LeadModal
          v-if="open"
          :key="leadModalKey"
          :open="open"
          :saving="saving"
          :deleting="deleting"
          :sync-saving="syncSaving"
          :form="form"
          :stages="stages"
          :qualification-loading="qualificationLoading"
          :qualification-saving="qualificationSaving"
          :qualification-applying="qualificationApplying"
          :qualification-session="qualificationSession"
          :qualification-history-session="qualificationHistorySession"
          :qualification-form="qualificationForm"
          :qualification-error="qualificationError"
          :qualification-scripts="qualificationScripts"
          :qualification-script-loading="qualificationScriptsLoading"
          :selected-qualification-script-id="selectedQualificationScriptId"
          :call-history="callHistoryRows"
          :call-history-lead-id="callHistoryLeadId"
          :call-history-loading="callHistoryLoading"
          :call-history-error="callHistoryError"
          :call-history-sync-error="callHistorySyncError"
          :sms-history="smsHistoryRows"
          :sms-history-lead-id="smsHistoryLeadId"
          :sms-history-loading="smsHistoryLoading"
          :sms-history-error="smsHistoryError"
          @close="closeModal"
          @save="saveRow"
          @delete="deleteCurrent"
          @sync-contact="syncCurrentLeadNow"
          @change-selected-qualification-script="onSelectedQualificationScriptChange"
          @qualify="reloadQualificationNow"
          @save-answer="saveQualificationAnswerNow"
          @complete-qualification="completeQualificationNow"
          @apply-recommended-stage="applyQualificationStageNow"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import AdminLayout from '../../layouts/AdminLayout.vue'
import LeadModal from '../../components/admin/LeadModal.vue'
import {
  applyLeadQualificationStage,
  completeLeadQualificationSession,
  deleteLead,
  fetchLeadAdNames,
  fetchLeadCallHistory,
  fetchLeadMapMarkers,
  fetchLeadQualificationSessions,
  fetchLeadSmsHistory,
  fetchQualificationScripts,
  fetchStages,
  geocodeLeadMapLead,
  geocodeLeadMapMissing,
  saveLead,
  saveLeadQualificationAnswer,
  startLeadQualification,
  syncLeadContact,
} from '../../api/admin'

const DEFAULT_CENTER = { lat: 39.8283, lng: -98.5795 }

let googleMapsPromise = null
let activeMarkers = []
let filterTimer = null
let geocodePollTimer = null
let qualificationLoadSeq = 0
let callHistoryLoadSeq = 0
let smsHistoryLoadSeq = 0
let leadRecordLoadSeq = 0
let geocodeCompletionNoticePending = false

const mapEl = ref(null)
const map = ref(null)
const infoWindow = ref(null)

const adName = ref('')
const adNames = ref([])
const stageId = ref('')
const stages = ref([])
const scope = ref('active')

const displayRows = ref([])
const unresolvedRows = ref([])
const selectedLead = ref(null)

const counts = ref({
  total: 0,
  mapped: 0,
  unmapped: 0,
})

const loading = ref(false)
const geocoding = ref(false)
const err = ref('')
const successMessage = ref('')

const open = ref(false)
const saving = ref(false)
const deleting = ref(false)
const syncSaving = ref(false)
const leadModalKey = ref(0)

const qualificationLoading = ref(false)
const qualificationSaving = ref(false)
const qualificationApplying = ref(false)
const qualificationScriptsLoading = ref(false)
const qualificationError = ref('')
const qualificationSession = ref(null)
const qualificationHistorySession = ref(null)
const qualificationScripts = ref([])
const selectedQualificationScriptId = ref('')

const callHistoryLoading = ref(false)
const callHistoryError = ref('')
const callHistorySyncError = ref('')
const callHistoryLeadId = ref(null)
const callHistoryRows = ref([])

const smsHistoryLoading = ref(false)
const smsHistoryError = ref('')
const smsHistoryLeadId = ref(null)
const smsHistoryRows = ref([])

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

const qualificationForm = reactive({
  step_id: null,
  option_id: '',
  answer_value: '',
  answer_text: '',
  note: '',
})

function stageLabel(stage) {
  if (!stage) {
    return ''
  }

  return stage.stage_group
      ? `${stage.stage_name} — ${stage.stage_group}`
      : stage.stage_name
}

function normalizeStageKey(row) {
  return String(row?.stage?.stage_name || row?.lead_status || '')
      .trim()
      .toLowerCase()
}

function stageColor(row) {
  const key = normalizeStageKey(row)

  if (key.includes('disqual')) return '#ef4444'
  if (key.includes('duplicate')) return '#f59e0b'
  if (key.includes('qual')) return '#10b981'
  if (key.includes('convert')) return '#0ea5e9'
  if (key.includes('sold')) return '#16a34a'
  if (key.includes('commun')) return '#3b82f6'
  if (key.includes('contact')) return '#3b82f6'
  if (key.includes('follow')) return '#8b5cf6'
  if (key.includes('review')) return '#8b5cf6'
  if (key.includes('pending')) return '#8b5cf6'
  if (key.includes('new')) return '#64748b'

  const palette = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#14b8a6', '#6366f1']
  const id = Number(row?.stage?.id || row?.lead_stage_id || 0)

  return palette[Math.abs(id) % palette.length]
}

function stageBadgeStyle(row) {
  const color = stageColor(row)

  return {
    borderColor: color,
    color,
    backgroundColor: `${color}14`,
  }
}

function displayStageName(row) {
  return row?.stage?.stage_name || row?.lead_status || 'Unstaged'
}

function locationLabel(row) {
  const pieces = [
    row?.resolved_city || row?.city || '',
    row?.resolved_state || row?.state || '',
  ].filter(Boolean)

  return pieces.length ? pieces.join(', ') : 'Unresolved'
}

function buildMarkerIcon(color) {
  const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="42" viewBox="0 0 30 42">
            <path d="M15 1C7.82 1 2 6.82 2 14c0 10.78 13 26 13 26s13-15.22 13-26C28 6.82 22.18 1 15 1z" fill="${color}" stroke="#0f172a" stroke-width="1.2"/>
            <circle cx="15" cy="14" r="5.4" fill="#ffffff" fill-opacity="0.92"/>
        </svg>
    `.trim()

  return {
    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
    scaledSize: new window.google.maps.Size(30, 42),
    anchor: new window.google.maps.Point(15, 42),
  }
}

function buildDisplayRows(rows) {
  const grouped = new Map()

  rows.forEach((row) => {
    const key = `${Number(row.lat).toFixed(6)}|${Number(row.lng).toFixed(6)}`
    const bucket = grouped.get(key) || []
    bucket.push(row)
    grouped.set(key, bucket)
  })

  const nextRows = []

  grouped.forEach((bucket) => {
    bucket.forEach((row, index) => {
      if (bucket.length === 1) {
        nextRows.push({
          ...row,
          display_lat: row.lat,
          display_lng: row.lng,
        })
        return
      }

      const angle = (Math.PI * 2 * index) / bucket.length
      const ring = Math.floor(index / 8)
      const radius = 0.012 + ring * 0.006
      const latOffset = Math.sin(angle) * radius
      const lngOffset = Math.cos(angle) * radius / Math.max(0.35, Math.cos((row.lat * Math.PI) / 180))

      nextRows.push({
        ...row,
        display_lat: row.lat + latOffset,
        display_lng: row.lng + lngOffset,
      })
    })
  })

  return nextRows
}

function escapeHtml(value) {
  return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;')
}

function extractErrorMessage(error, fallback = 'Request failed') {
  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    const firstValue = firstKey ? errors[firstKey] : null
    if (Array.isArray(firstValue) && firstValue.length) {
      return String(firstValue[0])
    }
  }

  return error?.response?.data?.message
      || error?.response?.data?.error
      || error?.message
      || fallback
}

function formatLeadReceivedAt(row) {
  const raw = row?.source_created_at || row?.lead_date_choice || row?.created_at || ''

  if (!raw) {
    return ''
  }

  const dt = new Date(raw)

  if (Number.isNaN(dt.getTime())) {
    return escapeHtml(String(raw))
  }

  return escapeHtml(
      dt.toLocaleString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
  )
}

function normalizePhoneHref(phone) {
  return String(phone || '').replace(/[^+\d]/g, '')
}

function buildInfoWindowHtml(row) {
  const name = escapeHtml(row.full_name || `Lead #${row.id}`)
  const receivedAt = formatLeadReceivedAt(row)
  const phone = String(row.phone || '').trim()
  const email = String(row.email || '').trim()
  const phoneHref = normalizePhoneHref(phone)

  return `
        <div style="min-width:240px;max-width:320px;line-height:1.4;">
            <div style="font-size:14px;font-weight:600;">
                <a
                  href="#"
                  data-lead-open-modal="1"
                  data-lead-id="${Number(row.id)}"
                  style="color:#2563eb;text-decoration:none;"
                >
                  ${name}
                </a>
            </div>
            ${receivedAt ? `<div style="margin-top:4px;font-size:12px;color:#64748b;">${receivedAt}</div>` : ''}
            ${phone ? `<div style="margin-top:4px;font-size:12px;"><a href="tel:${phoneHref}" style="color:#2563eb;text-decoration:none;">${escapeHtml(phone)}</a></div>` : ''}
            ${email ? `<div style="margin-top:4px;font-size:12px;"><a href="mailto:${escapeHtml(email)}" style="color:#2563eb;text-decoration:none;">${escapeHtml(email)}</a></div>` : ''}
        </div>
    `
}

function clearMessages() {
  err.value = ''
  successMessage.value = ''
}

function strContainsDisqual(value) {
  return String(value).includes('disqual')
}

function normalizeStatus(status) {
  return String(status ?? '').trim().toLowerCase()
}

function normalizeLeadStatusForForm(status, fallback = 'new') {
  const key = normalizeStatus(status)

  if (!key) return fallback
  if (key === 'new') return 'new'
  if (key === 'contacted' || key === 'needs_insurance') return 'contacted'
  if (key === 'qualified' || key === 'ready_for_senior_rep') return 'qualified'
  if (key === 'converted_to_carrier') return 'converted_to_carrier'
  if (key === 'duplicate') return 'duplicate'
  if (strContainsDisqual(key)) return 'disqualified'

  return fallback
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

function resetQualificationForm() {
  qualificationForm.step_id = null
  qualificationForm.option_id = ''
  qualificationForm.answer_value = ''
  qualificationForm.answer_text = ''
  qualificationForm.note = ''
}

function resetQualificationState() {
  qualificationLoading.value = false
  qualificationSaving.value = false
  qualificationApplying.value = false
  qualificationError.value = ''
  qualificationSession.value = null
  qualificationHistorySession.value = null
  selectedQualificationScriptId.value = ''
  resetQualificationForm()
}

function resetCallHistoryState() {
  callHistoryLoading.value = false
  callHistoryError.value = ''
  callHistorySyncError.value = ''
  callHistoryLeadId.value = null
  callHistoryRows.value = []
}

function resetSmsHistoryState() {
  smsHistoryLoading.value = false
  smsHistoryError.value = ''
  smsHistoryLeadId.value = null
  smsHistoryRows.value = []
}

function resetLeadModalState() {
  qualificationLoadSeq += 1
  callHistoryLoadSeq += 1
  smsHistoryLoadSeq += 1
  resetForm()
  resetQualificationState()
  resetCallHistoryState()
  resetSmsHistoryState()
}

function applyDialpadSaveMessages(response, baseSuccessMessage) {
  successMessage.value = baseSuccessMessage

  const sync = response?.dialpad_sync
  if (!sync) {
    return
  }

  if (sync.status === 'synced') {
    successMessage.value = `${baseSuccessMessage} Dialpad contact synced.`
    return
  }

  if (sync.status === 'failed') {
    err.value = `Dialpad sync failed: ${sync.message || 'Request failed'}`
  }
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

function applyLeadToForm(lead) {
  Object.assign(form, {
    id: lead?.id || null,
    source_name: lead?.source_name || '',
    ad_name: lead?.ad_name || '',
    platform: lead?.platform || '',
    source_created_at: lead?.source_created_at || '',
    lead_date_choice: lead?.lead_date_choice || '',
    insurance_answer: lead?.insurance_answer || '',
    full_name: lead?.full_name || '',
    email: lead?.email || '',
    phone: lead?.phone || '',
    city: lead?.city || '',
    state: lead?.state || '',
    carrier_class: lead?.carrier_class || '',
    usdot: lead?.usdot || '',
    truck_count: lead?.truck_count || '',
    trailer_count: lead?.trailer_count || '',
    lead_status: normalizeLeadStatusForForm(lead?.lead_status, 'new'),
    lead_stage_id: lead?.lead_stage_id ? String(lead.lead_stage_id) : (lead?.stage?.id ? String(lead.stage.id) : ''),
    notes: lead?.notes || '',
    duplicate_of_lead_id: lead?.duplicate_of_lead_id || null,
    duplicate_basis: lead?.duplicate_basis || '',
  })
}

async function fetchLeadRecord(id) {
  const response = await window.fetch(`/api/admin/leads/${id}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })

  const contentType = response.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')

  if (!isJson) {
    const text = await response.text()
    throw new Error(text?.trim() ? text.trim().slice(0, 220) : `Failed to load lead #${id}.`)
  }

  const data = await response.json()

  if (!response.ok) {
    throw new Error(data?.message || `Failed to load lead #${id}.`)
  }

  return data?.data ?? data
}

function startGeocodePolling() {
  if (geocodePollTimer) {
    return
  }

  geocodePollTimer = setInterval(() => {
    loadMarkers()
  }, 3000)
}

function stopGeocodePolling() {
  if (!geocodePollTimer) {
    return
  }

  clearInterval(geocodePollTimer)
  geocodePollTimer = null
}

function loadGoogleMapsApi() {
  if (window.google?.maps) {
    return Promise.resolve(window.google.maps)
  }

  const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

  if (!apiKey) {
    return Promise.reject(new Error('VITE_GOOGLE_MAPS_API_KEY is not configured.'))
  }

  if (!googleMapsPromise) {
    googleMapsPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-lead-map-google="1"]')

      if (existing) {
        existing.addEventListener('load', () => resolve(window.google.maps), { once: true })
        existing.addEventListener('error', () => reject(new Error('Google Maps failed to load.')), { once: true })
        return
      }

      const script = document.createElement('script')
      script.async = true
      script.defer = true
      script.dataset.leadMapGoogle = '1'
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}`

      script.onload = () => resolve(window.google.maps)
      script.onerror = () => reject(new Error('Google Maps failed to load.'))

      document.head.appendChild(script)
    })
  }

  return googleMapsPromise
}

async function ensureMap() {
  if (map.value) {
    return
  }

  await loadGoogleMapsApi()

  if (!mapEl.value) {
    return
  }

  map.value = new window.google.maps.Map(mapEl.value, {
    center: DEFAULT_CENTER,
    zoom: 4,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
  })

  infoWindow.value = new window.google.maps.InfoWindow()
}

function clearMarkers() {
  activeMarkers.forEach((marker) => marker.setMap(null))
  activeMarkers = []

  if (infoWindow.value) {
    infoWindow.value.close()
  }
}

function focusLead(row, marker = null) {
  selectedLead.value = row

  if (!infoWindow.value) {
    return
  }

  infoWindow.value.setContent(buildInfoWindowHtml(row))

  if (marker) {
    infoWindow.value.open({
      map: map.value,
      anchor: marker,
    })
  }
}

function handleInfoWindowDocumentClick(event) {
  const trigger = event.target.closest('[data-lead-open-modal="1"]')
  if (!trigger) {
    return
  }

  event.preventDefault()

  const leadId = Number(trigger.getAttribute('data-lead-id'))
  if (!leadId) {
    return
  }

  const row =
      displayRows.value.find((item) => Number(item.id) === leadId)
      || unresolvedRows.value.find((item) => Number(item.id) === leadId)
      || (selectedLead.value && Number(selectedLead.value.id) === leadId ? selectedLead.value : null)

  if (row) {
    openSharedLeadModal(row)
  }
}

function syncSelectedLead() {
  if (!selectedLead.value?.id) {
    return
  }

  const id = Number(selectedLead.value.id)
  const next =
      displayRows.value.find((row) => Number(row.id) === id)
      || unresolvedRows.value.find((row) => Number(row.id) === id)

  selectedLead.value = next || null
}

async function ensureQualificationScriptsLoaded() {
  if (qualificationScriptsLoading.value || qualificationScripts.value.length) {
    return
  }

  qualificationScriptsLoading.value = true

  try {
    const response = await fetchQualificationScripts({ active_only: 1 })
    qualificationScripts.value = Array.isArray(response?.data) ? response.data : []
  } finally {
    qualificationScriptsLoading.value = false
  }
}

function normalizeQualificationScriptId(value) {
  return value ? String(value) : ''
}

function findBestQualificationScriptId(leadLike = null) {
  const scripts = Array.isArray(qualificationScripts.value) ? qualificationScripts.value : []
  if (!scripts.length) return ''

  const adNameValue = String(leadLike?.ad_name ?? form.ad_name ?? '').trim().toLowerCase()
  const platformValue = String(leadLike?.platform ?? form.platform ?? '').trim().toLowerCase()

  if (adNameValue) {
    const adMatch = scripts.find((script) => String(script?.applies_to_ad_name ?? '').trim().toLowerCase() === adNameValue)
    if (adMatch?.id) {
      return String(adMatch.id)
    }
  }

  if (platformValue) {
    const platformMatch = scripts.find((script) => String(script?.applies_to_platform ?? '').trim().toLowerCase() === platformValue)
    if (platformMatch?.id) {
      return String(platformMatch.id)
    }
  }

  const defaultMatch = scripts.find((script) => Boolean(script?.is_default))
  if (defaultMatch?.id) {
    return String(defaultMatch.id)
  }

  return scripts[0]?.id ? String(scripts[0].id) : ''
}

function pickLatestAnsweredSession(sessions) {
  if (!Array.isArray(sessions)) {
    return null
  }

  return sessions.find((session) => Array.isArray(session?.answers) && session.answers.length > 0) || null
}

function syncQualificationFormFromSession() {
  resetQualificationForm()

  const step = qualificationSession.value?.current_step
  if (!step) return

  qualificationForm.step_id = step.id
}

function syncQualificationHistoryFromSession(session) {
  if (Array.isArray(session?.answers) && session.answers.length > 0) {
    qualificationHistorySession.value = session
  }
}

async function loadQualificationForLead(leadId, options = {}) {
  const seq = ++qualificationLoadSeq
  qualificationLoading.value = true
  qualificationSaving.value = false
  qualificationApplying.value = false
  qualificationError.value = ''
  qualificationSession.value = null
  qualificationHistorySession.value = null
  resetQualificationForm()

  try {
    await ensureQualificationScriptsLoaded()

    if (!selectedQualificationScriptId.value || options.preferDefaultScript) {
      selectedQualificationScriptId.value = findBestQualificationScriptId(form)
    }

    const requestPayload = selectedQualificationScriptId.value
        ? { qualification_script_id: Number(selectedQualificationScriptId.value) }
        : {}

    const [startResponse, historyResponse] = await Promise.all([
      startLeadQualification(leadId, requestPayload),
      fetchLeadQualificationSessions(leadId),
    ])

    if (seq !== qualificationLoadSeq || Number(form.id || 0) !== Number(leadId)) {
      return
    }

    qualificationSession.value = startResponse?.data ?? startResponse
    selectedQualificationScriptId.value = normalizeQualificationScriptId(
        qualificationSession.value?.script?.id || selectedQualificationScriptId.value
    )

    const sessions = Array.isArray(historyResponse?.data) ? historyResponse.data : []
    qualificationHistorySession.value = pickLatestAnsweredSession(sessions)

    syncQualificationFormFromSession()
    syncQualificationHistoryFromSession(qualificationSession.value)
  } catch (error) {
    if (seq !== qualificationLoadSeq || Number(form.id || 0) !== Number(leadId)) {
      return
    }

    qualificationError.value = extractErrorMessage(error)
  } finally {
    if (seq === qualificationLoadSeq && Number(form.id || 0) === Number(leadId)) {
      qualificationLoading.value = false
    }
  }
}

async function loadCallHistoryForLead(id) {
  const seq = ++callHistoryLoadSeq

  if (!id) {
    resetCallHistoryState()
    return
  }

  callHistoryLeadId.value = Number(id)
  callHistoryLoading.value = true
  callHistoryError.value = ''
  callHistorySyncError.value = ''
  callHistoryRows.value = []

  try {
    const data = await fetchLeadCallHistory(id, { sync: 1 })

    if (seq !== callHistoryLoadSeq || Number(form.id || 0) !== Number(id)) {
      return
    }

    const responseLeadId = Number(data?.meta?.lead_id || 0)
    if (responseLeadId !== Number(id)) {
      callHistoryRows.value = []
      callHistoryError.value = 'Call history response returned for the wrong lead.'
      return
    }

    callHistorySyncError.value = String(data?.meta?.sync_error || '')
    callHistoryRows.value = Array.isArray(data?.data)
        ? data.data.filter((item) => Number(item?.lead_id || 0) === Number(id))
        : []
  } catch (error) {
    if (seq !== callHistoryLoadSeq || Number(form.id || 0) !== Number(id)) {
      return
    }

    callHistoryRows.value = []
    callHistoryError.value = extractErrorMessage(error)
  } finally {
    if (seq === callHistoryLoadSeq && Number(form.id || 0) === Number(id)) {
      callHistoryLoading.value = false
    }
  }
}

async function loadSmsHistoryForLead(id) {
  const seq = ++smsHistoryLoadSeq

  if (!id) {
    resetSmsHistoryState()
    return
  }

  smsHistoryLeadId.value = Number(id)
  smsHistoryLoading.value = true
  smsHistoryError.value = ''
  smsHistoryRows.value = []

  try {
    const data = await fetchLeadSmsHistory(id)

    if (seq !== smsHistoryLoadSeq || Number(form.id || 0) !== Number(id)) {
      return
    }

    const responseLeadId = Number(data?.meta?.lead_id || 0)
    if (responseLeadId !== Number(id)) {
      smsHistoryRows.value = []
      smsHistoryError.value = 'SMS history response returned for the wrong lead.'
      return
    }

    smsHistoryRows.value = Array.isArray(data?.data)
        ? data.data.filter((item) => Number(item?.lead_id || 0) === Number(id))
        : []
  } catch (error) {
    if (seq !== smsHistoryLoadSeq || Number(form.id || 0) !== Number(id)) {
      return
    }

    smsHistoryRows.value = []
    smsHistoryError.value = extractErrorMessage(error)
  } finally {
    if (seq === smsHistoryLoadSeq && Number(form.id || 0) === Number(id)) {
      smsHistoryLoading.value = false
    }
  }
}

async function openSharedLeadModal(row, marker = null) {
  if (!row?.id) {
    return
  }

  clearMessages()
  focusLead(row, marker)

  const leadId = Number(row.id)
  const seq = ++leadRecordLoadSeq

  resetLeadModalState()
  open.value = false

  try {
    const lead = await fetchLeadRecord(leadId)

    if (seq !== leadRecordLoadSeq) {
      return
    }

    applyLeadToForm(lead)
    leadModalKey.value += 1
    open.value = true

    loadQualificationForLead(leadId, { preferDefaultScript: true })
    loadCallHistoryForLead(leadId)
    loadSmsHistoryForLead(leadId)
  } catch (error) {
    if (seq !== leadRecordLoadSeq) {
      return
    }

    err.value = extractErrorMessage(error, `Failed to load lead #${leadId}.`)
    open.value = false
  }
}

function closeModal() {
  open.value = false
  resetLeadModalState()
}

function onSelectedQualificationScriptChange(value) {
  selectedQualificationScriptId.value = normalizeQualificationScriptId(value)
}

function reloadQualificationNow() {
  if (!form.id) return
  loadQualificationForLead(form.id)
}

async function saveQualificationAnswerNow() {
  if (!qualificationSession.value?.id || !qualificationForm.step_id) {
    return
  }

  qualificationSaving.value = true
  qualificationError.value = ''

  try {
    const payload = {
      step_id: qualificationForm.step_id,
      option_id: qualificationForm.option_id ? Number(qualificationForm.option_id) : null,
      answer_value:
          qualificationForm.answer_value !== '' && qualificationForm.answer_value !== null
              ? String(qualificationForm.answer_value)
              : null,
      answer_text: qualificationForm.answer_text || null,
      note: qualificationForm.note || null,
    }

    const response = await saveLeadQualificationAnswer(qualificationSession.value.id, payload)
    qualificationSession.value = response?.data ?? response
    syncQualificationFormFromSession()
    syncQualificationHistoryFromSession(qualificationSession.value)
    successMessage.value = 'Qualification answer saved.'
  } catch (error) {
    qualificationError.value = extractErrorMessage(error)
  } finally {
    qualificationSaving.value = false
  }
}

async function completeQualificationNow() {
  if (!qualificationSession.value?.id) {
    return
  }

  qualificationSaving.value = true
  qualificationError.value = ''

  try {
    const response = await completeLeadQualificationSession(qualificationSession.value.id)
    qualificationSession.value = response?.data ?? response
    syncQualificationFormFromSession()
    syncQualificationHistoryFromSession(qualificationSession.value)

    if (qualificationSession.value?.recommended_status) {
      form.lead_status = normalizeLeadStatusForForm(
          qualificationSession.value.recommended_status,
          form.lead_status || 'new'
      )
    }

    successMessage.value = 'Qualification session completed.'
    await loadMarkers()
  } catch (error) {
    qualificationError.value = extractErrorMessage(error)
  } finally {
    qualificationSaving.value = false
  }
}

async function applyQualificationStageNow() {
  if (!qualificationSession.value?.id) {
    return
  }

  qualificationApplying.value = true
  qualificationError.value = ''

  try {
    const response = await applyLeadQualificationStage(qualificationSession.value.id)
    qualificationSession.value = response?.session ?? qualificationSession.value
    syncQualificationHistoryFromSession(qualificationSession.value)

    const updatedLead = response?.lead
    if (updatedLead) {
      if (updatedLead.lead_stage_id) {
        form.lead_stage_id = String(updatedLead.lead_stage_id)
      }

      if (updatedLead.lead_status) {
        form.lead_status = normalizeLeadStatusForForm(updatedLead.lead_status, form.lead_status || 'new')
      }

      if (typeof updatedLead.notes === 'string') {
        form.notes = updatedLead.notes
      }
    } else {
      if (qualificationSession.value?.recommended_stage_id) {
        form.lead_stage_id = String(qualificationSession.value.recommended_stage_id)
      }

      if (qualificationSession.value?.recommended_status) {
        form.lead_status = normalizeLeadStatusForForm(
            qualificationSession.value.recommended_status,
            form.lead_status || 'new'
        )
      }
    }

    successMessage.value = 'Recommended stage applied to lead.'
    await loadMarkers()
  } catch (error) {
    qualificationError.value = extractErrorMessage(error)
  } finally {
    qualificationApplying.value = false
  }
}

async function saveRow() {
  saving.value = true
  err.value = ''

  try {
    const response = await saveLead(buildLeadPayload(), form.id)
    open.value = false
    resetLeadModalState()
    applyDialpadSaveMessages(response, 'Lead saved.')
    await loadMarkers()
  } catch (error) {
    err.value = extractErrorMessage(error)
  } finally {
    saving.value = false
  }
}

async function syncCurrentLeadNow() {
  if (!form.id) return

  syncSaving.value = true
  err.value = ''

  try {
    const response = await syncLeadContact(form.id)
    const sync = response?.dialpad_sync
    successMessage.value = sync?.message || 'Dialpad contact synced.'
    await loadMarkers()
  } catch (error) {
    err.value = extractErrorMessage(error)
  } finally {
    syncSaving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  err.value = ''

  try {
    await deleteLead(form.id)
    open.value = false
    resetLeadModalState()
    successMessage.value = 'Lead deleted.'
    await loadMarkers()
  } catch (error) {
    err.value = extractErrorMessage(error)
  } finally {
    deleting.value = false
  }
}

function renderMarkers() {
  clearMarkers()

  if (!map.value || !displayRows.value.length) {
    selectedLead.value = null

    if (map.value) {
      map.value.setCenter(DEFAULT_CENTER)
      map.value.setZoom(4)
    }

    return
  }

  const bounds = new window.google.maps.LatLngBounds()

  activeMarkers = displayRows.value.map((row) => {
    const marker = new window.google.maps.Marker({
      map: map.value,
      position: {
        lat: row.display_lat,
        lng: row.display_lng,
      },
      title: row.full_name || `Lead #${row.id}`,
      icon: buildMarkerIcon(stageColor(row)),
    })

    marker.addListener('mouseover', () => {
      if (!infoWindow.value) return
      infoWindow.value.setContent(buildInfoWindowHtml(row))
      infoWindow.value.open({
        map: map.value,
        anchor: marker,
      })
    })

    marker.addListener('mouseout', () => {
      if (selectedLead.value?.id !== row.id && infoWindow.value) {
        infoWindow.value.close()
      }
    })

    marker.addListener('click', () => {
      focusLead(row, marker)
    })

    bounds.extend({
      lat: row.display_lat,
      lng: row.display_lng,
    })

    return marker
  })

  map.value.fitBounds(bounds, 60)
}

async function loadAdNames() {
  const data = await fetchLeadAdNames()
  adNames.value = Array.isArray(data?.data) ? [...data.data] : []
}

async function loadStages() {
  if (!adName.value) {
    stages.value = []
    return
  }

  const data = await fetchStages({
    group: adName.value || undefined,
  })

  stages.value = Array.isArray(data?.data) ? [...data.data] : []
}

async function loadMarkers() {
  if (!adName.value) {
    displayRows.value = []
    unresolvedRows.value = []
    counts.value = { total: 0, mapped: 0, unmapped: 0 }
    clearMarkers()
    selectedLead.value = null
    geocoding.value = false
    stopGeocodePolling()
    return
  }

  loading.value = true
  err.value = ''

  try {
    const data = await fetchLeadMapMarkers({
      ad_name: adName.value,
      stage_id: stageId.value || undefined,
      scope: scope.value,
    })

    counts.value = {
      total: Number(data?.meta?.counts?.total ?? 0),
      mapped: Number(data?.meta?.counts?.mapped ?? 0),
      unmapped: Number(data?.meta?.counts?.unmapped ?? 0),
    }

    displayRows.value = buildDisplayRows(Array.isArray(data?.data) ? data.data : [])
    unresolvedRows.value = Array.isArray(data?.meta?.unresolved) ? data.meta.unresolved : []

    syncSelectedLead()

    const processing = Boolean(data?.meta?.processing)
    const processingStatus = String(data?.meta?.processing_status || '')
    const processingMessage = String(data?.meta?.processing_message || '')

    if (processing) {
      geocoding.value = true
      startGeocodePolling()
    } else {
      if (geocoding.value && geocodeCompletionNoticePending) {
        if (processingStatus === 'failed') {
          err.value = processingMessage || 'Background geocoding failed.'
        } else {
          successMessage.value = 'Background geocoding finished. Remaining unmapped leads need manual review.'
        }
      } else if (processingStatus === 'failed' && processingMessage) {
        err.value = processingMessage
      }

      geocoding.value = false
      geocodeCompletionNoticePending = false
      stopGeocodePolling()
    }

    await ensureMap()
    renderMarkers()
  } catch (error) {
    err.value = extractErrorMessage(error, 'Failed to load lead map.')
    displayRows.value = []
    unresolvedRows.value = []
    counts.value = { total: 0, mapped: 0, unmapped: 0 }
    clearMarkers()
    selectedLead.value = null
    stopGeocodePolling()
    geocoding.value = false
  } finally {
    loading.value = false
  }
}

function scheduleReload() {
  if (filterTimer) {
    clearTimeout(filterTimer)
  }

  filterTimer = setTimeout(() => {
    loadMarkers()
  }, 200)
}

async function reloadAll() {
  err.value = ''
  successMessage.value = ''
  await loadAdNames()
  await loadStages()
  await loadMarkers()
}

async function geocodeMissing() {
  if (!adName.value) {
    return
  }

  geocoding.value = true
  geocodeCompletionNoticePending = true
  err.value = ''
  successMessage.value = ''

  try {
    const data = await geocodeLeadMapMissing({
      ad_name: adName.value,
      stage_id: stageId.value || undefined,
      scope: scope.value,
    })

    const alreadyRunning = Boolean(data?.meta?.already_running)

    successMessage.value = alreadyRunning
        ? 'Background geocoding is already running. The map will refresh automatically.'
        : 'Background geocoding started. The map will refresh automatically until all geocodable leads are processed.'

    startGeocodePolling()
    await loadMarkers()
  } catch (error) {
    geocoding.value = false
    geocodeCompletionNoticePending = false
    stopGeocodePolling()
    err.value = extractErrorMessage(error, 'Failed to start background geocoding.')
  }
}

async function geocodeSingleLead(id) {
  geocoding.value = true
  err.value = ''
  successMessage.value = ''

  try {
    const data = await geocodeLeadMapLead(id, { force: true })
    const status = data?.data?.status || 'done'
    successMessage.value = `Lead #${id} geocode status: ${status}.`
    await loadMarkers()
  } catch (error) {
    err.value = extractErrorMessage(error, `Failed to geocode lead #${id}.`)
  } finally {
    if (!geocodePollTimer) {
      geocoding.value = false
    }
  }
}

watch(adName, async () => {
  stageId.value = ''
  await loadStages()
  scheduleReload()
})

watch([stageId, scope], () => {
  scheduleReload()
})

onMounted(async () => {
  document.addEventListener('click', handleInfoWindowDocumentClick)

  try {
    await ensureMap()
  } catch (error) {
    err.value = extractErrorMessage(error, 'Google Maps failed to initialize.')
  }

  await loadAdNames()
  await loadStages()
  await loadMarkers()
})

onBeforeUnmount(() => {
  if (filterTimer) {
    clearTimeout(filterTimer)
  }

  stopGeocodePolling()
  document.removeEventListener('click', handleInfoWindowDocumentClick)
  clearMarkers()
})
</script>