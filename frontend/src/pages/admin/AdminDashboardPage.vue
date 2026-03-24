<template>
  <AdminLayout>
    <div class="dashboard-page space-y-1">
      <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <h1 class="text-xl font-semibold leading-tight text-slate-900">Dashboard</h1>
      </div>

      <div
          v-if="err"
          class="rounded-xl border border-rose-200 bg-rose-50 px-2 py-1.5 text-sm text-rose-700 shadow-sm"
      >
        {{ err }}
      </div>

      <div class="grid grid-cols-1 gap-1 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Admin Users</div>
          <div class="mt-0.5 text-xl font-semibold leading-none text-slate-900">{{ cards.admin_users }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Leads Total</div>
          <div class="mt-0.5 text-xl font-semibold leading-none text-slate-900">{{ cards.leads_total }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Leads New</div>
          <div class="mt-0.5 text-xl font-semibold leading-none text-slate-900">{{ cards.leads_new }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Carriers Total</div>
          <div class="mt-0.5 text-xl font-semibold leading-none text-slate-900">{{ cards.carriers_total }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm">
          <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Jobs Open</div>
          <div class="mt-0.5 text-xl font-semibold leading-none text-slate-900">{{ cards.jobs_open }}</div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
        <div class="mb-1">
          <div class="text-base font-semibold leading-tight text-slate-900">Upcoming Jobs (Next 30 Days)</div>
          <div class="mt-0.5 text-[11px] text-slate-500">Roster fill snapshot for jobs starting soon</div>
        </div>

        <div
            v-if="loading"
            class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-center text-sm text-slate-500"
        >
          Loading upcoming jobs.
        </div>

        <template v-else-if="upcomingRows.length">
          <div class="mb-1 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-[10px] text-slate-500">
              Showing {{ pageStart }} to {{ pageEnd }} of {{ upcomingRows.length }} jobs
            </div>

            <div class="flex items-center gap-1">
              <button
                  type="button"
                  class="pager-btn"
                  :disabled="jobsPage <= 1"
                  @click="goToPage(1)"
              >
                «
              </button>

              <button
                  type="button"
                  class="pager-btn"
                  :disabled="jobsPage <= 1"
                  @click="goToPage(jobsPage - 1)"
              >
                ‹
              </button>

              <span class="rounded-lg border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-medium text-slate-700">
                {{ jobsPage }}
              </span>

              <button
                  type="button"
                  class="pager-btn"
                  :disabled="jobsPage >= jobsTotalPages"
                  @click="goToPage(jobsPage + 1)"
              >
                ›
              </button>

              <button
                  type="button"
                  class="pager-btn"
                  :disabled="jobsPage >= jobsTotalPages"
                  @click="goToPage(jobsTotalPages)"
              >
                »
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-1 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="row in pagedUpcomingRows"
                :key="row.id"
                class="self-start rounded-lg border border-slate-200 bg-white px-1 py-1 shadow-sm"
            >
              <div class="flex items-start justify-between gap-1">
                <div class="min-w-0 flex-1">
                  <div class="truncate text-[12px] font-semibold leading-tight text-slate-900">
                    {{ row.job_number || '—' }} | {{ row.title || 'Untitled Job' }}
                  </div>

                  <div class="mt-0.5 flex items-center justify-between gap-2">
                    <div class="min-w-0 truncate text-[10px] leading-tight text-slate-600">
                      Job Start Date: {{ displayDate(row.job_start_date) }}
                    </div>

                    <div class="shrink-0 flex items-center gap-2 leading-none">
                      <div class="whitespace-nowrap text-[17px] font-extrabold text-slate-900">
                        Filled: {{ rosterSummaryLabel(row) }}
                      </div>

                      <span class="percent-badge">
                        {{ percentLabel(row) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-0.5 rounded-md border border-slate-200 bg-white p-0 shadow-sm select-none">
                <div class="roster-head">
                  <div class="truncate text-center">Carrier</div>
                  <div class="truncate text-center">Driver</div>
                  <div class="truncate text-center">Status</div>
                </div>

                <div class="max-h-72 overflow-y-auto">
                  <div
                      v-for="(slot, index) in rosterSlotItems(row)"
                      :key="`${row.id}-${slot.slot_type}-${slot.slot_number}-${index}`"
                      :class="slotRowClass(slot)"
                  >
                    <div class="truncate text-center font-semibold">
                      {{ slotCarrierText(slot) }}
                    </div>

                    <div class="truncate text-center">
                      {{ slotDriverText(slot) }}
                    </div>

                    <div class="flex justify-center">
                      <span :class="slotBadgeClass(slot)">
                        {{ slotStatusLabel(slot) }}
                      </span>
                    </div>
                  </div>

                  <div
                      v-if="!rosterSlotItems(row).length"
                      class="px-1 py-1 text-center text-[11px] leading-tight text-slate-400"
                  >
                    —
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div
            v-else
            class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-center text-sm text-slate-500"
        >
          No upcoming jobs found.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import AdminLayout from '../../layouts/AdminLayout.vue'
import { fetchDashboard } from '../../api/admin'

const loading = ref(false)
const err = ref('')
const dashboard = ref({})

const jobsPage = ref(1)
const jobsPageSize = 9

const cards = computed(() => {
  const raw = dashboard.value?.cards || {}
  return {
    admin_users: Number(raw.admin_users || 0),
    leads_total: Number(raw.leads_total || 0),
    leads_new: Number(raw.leads_new || 0),
    carriers_total: Number(raw.carriers_total || 0),
    jobs_open: Number(raw.jobs_open || 0),
  }
})

function normalizeDateText(value) {
  const text = String(value || '').trim()
  if (!text) return ''

  const dateOnly = text.includes('T') ? text.slice(0, 10) : text
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateOnly)) {
    return dateOnly
  }

  const parsed = new Date(text)
  if (!Number.isNaN(parsed.getTime())) {
    const year = parsed.getFullYear()
    const month = String(parsed.getMonth() + 1).padStart(2, '0')
    const day = String(parsed.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  return ''
}

function toSortValue(value) {
  const normalized = normalizeDateText(value)
  if (!normalized) return Number.MAX_SAFE_INTEGER

  const [year, month, day] = normalized.split('-').map(Number)
  return Date.UTC(year, month - 1, day)
}

const upcomingRows = computed(() => {
  const raw = Array.isArray(dashboard.value?.upcoming_jobs)
      ? dashboard.value.upcoming_jobs
      : Array.isArray(dashboard.value?.upcomingJobs)
          ? dashboard.value.upcomingJobs
          : Array.isArray(dashboard.value?.jobs_upcoming)
              ? dashboard.value.jobs_upcoming
              : []

  return [...raw].sort((a, b) => {
    const diff = toSortValue(a?.job_start_date) - toSortValue(b?.job_start_date)
    if (diff !== 0) return diff
    return String(a?.job_number || '').localeCompare(String(b?.job_number || ''))
  })
})

const jobsTotalPages = computed(() => {
  const total = Math.ceil(upcomingRows.value.length / jobsPageSize)
  return total > 0 ? total : 1
})

const pageStart = computed(() => {
  if (!upcomingRows.value.length) return 0
  return (jobsPage.value - 1) * jobsPageSize + 1
})

const pageEnd = computed(() => {
  if (!upcomingRows.value.length) return 0
  return Math.min(jobsPage.value * jobsPageSize, upcomingRows.value.length)
})

const pagedUpcomingRows = computed(() => {
  const start = (jobsPage.value - 1) * jobsPageSize
  const end = start + jobsPageSize
  return upcomingRows.value.slice(start, end)
})

watch(upcomingRows, () => {
  if (jobsPage.value > jobsTotalPages.value) {
    jobsPage.value = jobsTotalPages.value
  }
  if (jobsPage.value < 1) {
    jobsPage.value = 1
  }
})

function goToPage(page) {
  if (page < 1) {
    jobsPage.value = 1
    return
  }

  if (page > jobsTotalPages.value) {
    jobsPage.value = jobsTotalPages.value
    return
  }

  jobsPage.value = page
}

function displayDate(value) {
  const normalized = normalizeDateText(value)
  if (!normalized) return '—'

  const [year, month, day] = normalized.split('-')
  return `${month}-${day}-${year}`
}

function rosterSummary(row) {
  const summary = row?.roster_summary || {}
  return {
    primary_filled: Number(summary.primary_filled ?? row?.primary_filled ?? 0),
    primary_required: Number(summary.primary_required ?? row?.primary_required ?? 0),
    spare_filled: Number(summary.spare_filled ?? row?.spare_filled ?? 0),
  }
}

function rosterSummaryLabel(row) {
  const summary = rosterSummary(row)
  return `${summary.primary_filled} / ${summary.primary_required} + ${summary.spare_filled}`
}

function percentValue(row) {
  const direct = row?.fill_percent ?? row?.percent_filled ?? row?.fill_percentage
  if (direct !== null && direct !== undefined && direct !== '') {
    const numeric = Number(direct)
    return Number.isFinite(numeric) ? Math.max(0, Math.round(numeric)) : 0
  }

  const summary = row?.roster_summary || {}
  const nested = Number(summary.fill_percent)
  if (Number.isFinite(nested) && nested > 0) {
    return Math.max(0, Math.round(nested))
  }

  const counts = rosterSummary(row)
  if (counts.primary_required <= 0) {
    return 0
  }

  return Math.max(0, Math.round((counts.primary_filled / counts.primary_required) * 100))
}

function percentLabel(row) {
  return `${percentValue(row)}%`
}

function rosterSlotItems(row) {
  if (Array.isArray(row?.roster_slots)) return row.roster_slots
  return []
}

function firstFilledValue(...values) {
  for (const value of values) {
    const text = String(value ?? '').trim()
    if (text) return text
  }
  return ''
}

function nestedFieldText(obj, paths) {
  for (const path of paths) {
    const keys = path.split('.')
    let current = obj

    for (const key of keys) {
      if (current == null || typeof current !== 'object') {
        current = null
        break
      }
      current = current[key]
    }

    const text = String(current ?? '').trim()
    if (text) return text
  }

  return ''
}

function normalizedSlotStatusKey(slot) {
  const rawKey = String(slot?.status_key || '').trim().toLowerCase()

  if (rawKey === 'ready') return 'ready'
  if (rawKey === 'pending_paperwork') return 'pending_paperwork'
  if (rawKey === 'open_alternate') return 'open_alternate'
  if (rawKey === 'open') return 'open'

  const label = String(slot?.status_label || '').trim().toLowerCase()

  if (label.includes('pending')) return 'pending_paperwork'
  if (label.includes('alternate') || label.includes('on-call') || label.includes('on call')) return 'open_alternate'
  if (label.includes('ready')) return 'ready'

  return 'open'
}

function slotStatusLabel(slot) {
  const label = firstFilledValue(slot?.status_label)
  if (label) return label

  switch (normalizedSlotStatusKey(slot)) {
    case 'ready':
      return 'Ready'
    case 'pending_paperwork':
      return 'Pending paperwork'
    case 'open_alternate':
      return 'Open on-call'
    case 'open':
    default:
      return 'Open'
  }
}

function slotCarrierText(slot) {
  const statusKey = normalizedSlotStatusKey(slot)

  if (statusKey === 'open_alternate') {
    return firstFilledValue(
        nestedFieldText(slot, [
          'carrier.name',
          'carrier.company_name',
          'carrier.business_name',
          'carrier.mc_name',
        ]),
        slot?.carrier_name,
        slot?.carrier_company,
        slot?.company_name,
        slot?.company,
        slot?.carrier_label,
        slot?.business_name,
        slot?.mc_name,
        slot?.slot_label,
        slot?.display_name,
        `Open on-call ${slot?.slot_number || ''}`.trim(),
        'Open on-call'
    )
  }

  if (statusKey === 'open') {
    return firstFilledValue(
        nestedFieldText(slot, [
          'carrier.name',
          'carrier.company_name',
          'carrier.business_name',
          'carrier.mc_name',
        ]),
        slot?.carrier_name,
        slot?.carrier_company,
        slot?.company_name,
        slot?.company,
        slot?.carrier_label,
        slot?.business_name,
        slot?.mc_name,
        slot?.slot_label,
        slot?.display_name,
        `Open position ${slot?.slot_number || ''}`.trim(),
        'Open'
    )
  }

  return firstFilledValue(
      nestedFieldText(slot, [
        'carrier.name',
        'carrier.company_name',
        'carrier.business_name',
        'carrier.mc_name',
      ]),
      slot?.carrier_name,
      slot?.carrier_company,
      slot?.company_name,
      slot?.company,
      slot?.carrier_label,
      slot?.business_name,
      slot?.mc_name,
      slot?.carrier_display_name,
      slot?.carrier_business_name,
      slot?.carrier_mc_name,
      '—'
  )
}

function slotDriverText(slot) {
  return firstFilledValue(
      nestedFieldText(slot, [
        'driver.full_name',
        'driver.name',
        'driver.driver_name',
        'driver_contact.full_name',
        'driver_contact.name',
        'roster_driver.full_name',
        'roster_driver.name',
      ]),
      slot?.driver_full_name,
      slot?.driver_name,
      slot?.driver_display_name,
      slot?.driver,
      '—'
  )
}

function slotRowClass(slot) {
  const base = 'grid grid-cols-3 items-center gap-0 border-b px-0 py-[1px] text-[14px] leading-[1.1] last:border-b-0'

  switch (normalizedSlotStatusKey(slot)) {
    case 'ready':
      return `${base} border-emerald-200 bg-emerald-50 text-emerald-900`
    case 'pending_paperwork':
      return `${base} border-amber-200 bg-amber-50 text-amber-900`
    case 'open_alternate':
      return `${base} border-indigo-200 bg-indigo-50 text-indigo-900`
    case 'open':
    default:
      return `${base} border-slate-200 bg-slate-50 text-slate-800`
  }
}

function slotBadgeClass(slot) {
  const base = 'inline-flex min-w-[118px] items-center justify-center rounded-full border px-2 py-[1px] text-[13px] font-semibold leading-none whitespace-nowrap'

  switch (normalizedSlotStatusKey(slot)) {
    case 'ready':
      return `${base} border-emerald-200 bg-white text-emerald-700`
    case 'pending_paperwork':
      return `${base} border-amber-200 bg-white text-amber-700`
    case 'open_alternate':
      return `${base} border-indigo-200 bg-white text-indigo-700`
    case 'open':
    default:
      return `${base} border-slate-200 bg-white text-slate-600`
  }
}

async function loadDashboard() {
  loading.value = true
  err.value = ''

  try {
    dashboard.value = await fetchDashboard()
    jobsPage.value = 1
  } catch (e) {
    err.value = e?.response?.data?.message || e?.message || 'Failed to load dashboard.'
    dashboard.value = {}
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDashboard()
})
</script>

<style scoped>
.percent-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 42px;
  height: 20px;
  border-radius: 999px;
  border: 1px solid #93c5fd;
  background: #dbeafe;
  padding: 0 6px;
  font-size: 12px;
  font-weight: 800;
  line-height: 1;
  color: #1d4ed8;
}

.pager-btn {
  min-width: 22px;
  height: 22px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  padding: 0 5px;
  font-size: 10px;
  font-weight: 600;
  color: #334155;
}

.pager-btn:hover:not(:disabled) {
  background: #f8fafc;
}

.pager-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.roster-head {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  padding: 3px 0;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  color: #475569;
}
</style>