<template>
  <BaseAdminModal
      :open="open"
      title="Job Roster"
      :saving="saving"
      :record-id="null"
      save-label="Close"
      size-memory-key="job-roster-modal"
      @close="$emit('close')"
      @save="$emit('close')"
      @delete="noop"
  >
    <div class="space-y-3">
      <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="font-semibold text-slate-900">{{ jobLabel }}</div>
            <div class="mt-1">Job Start Date: {{ displayStartDate }}</div>
            <div class="mt-1">
              Manual roster list only for now. Blank rows are generated from
              <span class="font-semibold">Primary Required</span> and
              <span class="font-semibold">Spare Allowed</span>.
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <div
                class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                :class="summaryClass"
            >
              {{ summaryText }}
            </div>

            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="startCreate"
            >
              Add Row
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-3">
        <div class="mb-2 text-xs font-semibold text-gray-900">
          {{ form.id ? 'Edit Roster Row' : 'New Roster Row' }}
        </div>

        <div class="grid grid-cols-1 gap-y-1.5 md:grid-cols-2 md:gap-x-4">
          <ModalFieldRow label="Slot Type:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <select v-model="form.slot_type" class="field-input">
                <option value="primary">Primary</option>
                <option value="spare">Spare</option>
              </select>
              <InlineFieldHelp text="Primary rows count toward the required target. Spare rows are backup or alternate capacity." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Status:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <select v-model="form.status" class="field-input">
                <option value="open">Open</option>
                <option value="ready">Ready</option>
                <option value="pending_paperwork">Pending paperwork</option>
                <option value="open_alternate">Open alternate</option>
              </select>
              <InlineFieldHelp text="Manual roster status for this slot. Use Open or Open alternate for empty slots, and Ready or Pending paperwork when a carrier or driver is being worked." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Carrier:" class="md:col-span-1">
            <div class="field-inline max-w-[320px]">
              <input v-model="form.carrier_name" maxlength="25" class="field-input" />
              <InlineFieldHelp text="Carrier name entered by hand for this roster row. This does not create or link to a carrier record." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Driver Name:" class="md:col-span-1">
            <div class="field-inline max-w-[320px]">
              <input v-model="form.driver_name" maxlength="25" class="field-input" />
              <InlineFieldHelp text="Driver name entered by hand for this roster row. A driver name can be used only once per job roster." />
            </div>
          </ModalFieldRow>
        </div>

        <div v-if="error" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
          {{ error }}
        </div>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button
              type="button"
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="resetForm"
          >
            {{ form.id ? 'New Row' : 'Clear' }}
          </button>

          <button
              v-if="form.id"
              type="button"
              class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50"
              :disabled="savingRow || saving"
              @click="deleteCurrentRow"
          >
            Delete
          </button>

          <button
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50"
              :disabled="savingRow"
              @click="saveForm"
          >
            {{ savingRow ? 'Saving...' : (form.id ? 'Update Row' : 'Add Row') }}
          </button>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-3">
        <div class="mb-2 flex items-center justify-between gap-2">
          <div class="text-xs font-semibold text-gray-900">Roster</div>
          <div class="text-[11px] text-slate-500">
            {{ loading ? 'Loading...' : `${rows.length} row${rows.length === 1 ? '' : 's'}` }}
          </div>
        </div>

        <div class="mb-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
          Click the carrier column to open a row for edit.
        </div>

        <div v-if="loading" class="px-1 py-2 text-xs text-slate-600">
          Loading roster...
        </div>

        <div v-else>
          <RosterTable
              :rows="rows"
              @edit="editRow"
          />
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'
import InlineFieldHelp from './InlineFieldHelp.vue'
import RosterTable from './RosterTable.vue'
import {
  createJobAssignment,
  deleteJobAssignment,
  fetchJobRoster,
  updateJobAssignment,
} from '../../api/admin'

const props = defineProps({
  open: { type: Boolean, default: false },
  job: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'updated'])

const loading = ref(false)
const saving = ref(false)
const savingRow = ref(false)
const error = ref('')
const rows = ref([])
const summary = ref({
  primary_required: 0,
  primary_filled: 0,
  spare_allowed: 0,
  spare_filled: 0,
  primary_overfill: 0,
  spare_overfill: 0,
})

const form = reactive({
  id: null,
  slot_type: 'primary',
  carrier_name: '',
  driver_name: '',
  status: 'open',
})

let rosterSeq = 0

const jobLabel = computed(() => {
  if (!props.job) return 'Job Roster'
  return `${props.job.job_number || '—'} | ${props.job.title || 'Untitled Job'}`
})

const displayStartDate = computed(() => {
  const text = String(props.job?.job_start_date || '')
  if (!text) return '—'
  return text.includes('T') ? text.slice(0, 10) : text
})

const summaryText = computed(() => {
  return `Roster: ${summary.value.primary_filled || 0} / ${summary.value.primary_required || 0} + ${summary.value.spare_filled || 0}`
})

const summaryClass = computed(() => {
  if ((summary.value.primary_overfill || 0) > 0 || (summary.value.spare_overfill || 0) > 0) {
    return 'border-rose-200 bg-rose-50 text-rose-700'
  }

  if ((summary.value.primary_filled || 0) >= (summary.value.primary_required || 0) && (summary.value.primary_required || 0) > 0) {
    return 'border-amber-200 bg-amber-50 text-amber-700'
  }

  return 'border-sky-200 bg-sky-50 text-sky-700'
})

watch(
    () => props.open,
    async (value) => {
      if (value && props.job?.id) {
        resetForm()
        await loadRoster()
      }
    },
    { immediate: true }
)

watch(
    () => form.slot_type,
    (value) => {
      if (!form.id && (form.status === 'open' || form.status === 'open_alternate')) {
        form.status = value === 'spare' ? 'open_alternate' : 'open'
      }
    }
)

function noop() {}

function startCreate() {
  resetForm()
}

function resetForm() {
  Object.assign(form, {
    id: null,
    slot_type: 'primary',
    carrier_name: '',
    driver_name: '',
    status: 'open',
  })

  error.value = ''
}

async function loadRoster() {
  if (!props.job?.id) return

  const seq = ++rosterSeq
  loading.value = true
  error.value = ''

  try {
    const response = await fetchJobRoster(props.job.id)
    if (seq !== rosterSeq) return

    rows.value = Array.isArray(response?.rows) ? response.rows : []
    summary.value = response?.summary || {
      primary_required: 0,
      primary_filled: 0,
      spare_allowed: 0,
      spare_filled: 0,
      primary_overfill: 0,
      spare_overfill: 0,
    }
  } catch (e) {
    if (seq !== rosterSeq) return
    error.value = extractErrorMessage(e)
  } finally {
    if (seq === rosterSeq) {
      loading.value = false
    }
  }
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    slot_type: row.slot_type || 'primary',
    carrier_name: row.carrier_name || '',
    driver_name: row.driver_name || '',
    status: row.status || (row.slot_type === 'spare' ? 'open_alternate' : 'open'),
  })

  error.value = ''
}

function normalizeDriverName(value) {
  return String(value || '')
      .trim()
      .replace(/\s+/g, ' ')
      .toLowerCase()
}

function duplicateDriverRow() {
  const normalized = normalizeDriverName(form.driver_name)

  if (!normalized) {
    return null
  }

  return rows.value.find((row) => {
    if (Number(row?.id || 0) === Number(form.id || 0)) {
      return false
    }

    return normalizeDriverName(row?.driver_name) === normalized
  }) || null
}

function duplicateDriverMessage(row) {
  const slot = row?.slot_label || row?.slot_type || 'another row'
  const driver = String(form.driver_name || '').trim().replace(/\s+/g, ' ')

  return `Driver "${driver}" is already used in ${slot}. Please choose a different driver name.`
}

async function saveForm() {
  if (!props.job?.id) return

  error.value = ''

  const duplicateRow = duplicateDriverRow()
  if (duplicateRow) {
    error.value = duplicateDriverMessage(duplicateRow)
    return
  }

  savingRow.value = true

  const payload = {
    slot_type: form.slot_type,
    carrier_name: form.carrier_name || null,
    driver_name: form.driver_name || null,
    status: form.status,
  }

  try {
    if (form.id) {
      await updateJobAssignment(form.id, payload)
    } else {
      await createJobAssignment(props.job.id, payload)
    }

    resetForm()
    await loadRoster()
    emit('updated')
  } catch (e) {
    error.value = extractErrorMessage(e)
  } finally {
    savingRow.value = false
  }
}

async function deleteCurrentRow() {
  if (!form.id) return

  saving.value = true
  error.value = ''

  try {
    await deleteJobAssignment(form.id)
    resetForm()
    await loadRoster()
    emit('updated')
  } catch (e) {
    error.value = extractErrorMessage(e)
  } finally {
    saving.value = false
  }
}

function extractErrorMessage(e) {
  return e?.response?.data?.message || Object.values(e?.response?.data?.errors || {})?.[0]?.[0] || e?.message || 'Request failed'
}
</script>

<style scoped>
.field-inline {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  width: 100%;
  min-width: 0;
}

.field-input,
.field-textarea {
  width: 100%;
  min-width: 0;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  line-height: 1.2rem;
  color: #111827;
  background: #fff;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #94a3b8;
}
</style>