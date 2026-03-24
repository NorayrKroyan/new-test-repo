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
    <div class="relative space-y-3">
      <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="font-semibold text-slate-900">{{ jobLabel }}</div>
            <div class="mt-1">Job Start Date: {{ displayStartDate }}</div>
            <div class="mt-1">
              Manual roster list only for now. Blank rows are generated from
              <span class="font-semibold">Primary Required</span> and
              <span class="font-semibold">On-Call Allowed</span>.
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

      <div
          v-if="error && !rowModalOpen"
          class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
      >
        {{ error }}
      </div>

      <div class="rounded-lg border border-gray-200 bg-white p-3">
        <div class="mb-2 flex items-center justify-between gap-2">
          <div class="text-xs font-semibold text-gray-900">Roster</div>
          <div class="text-[11px] text-slate-500">
            {{ loading ? 'Loading...' : `${rows.length} row${rows.length === 1 ? '' : 's'}` }}
          </div>
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

      <div
          v-if="rowModalOpen"
          class="roster-editor-overlay"
          @click.self="closeRowEditor"
      >
        <div
            ref="rowModalRef"
            class="roster-editor-card"
            :style="rowModalStyle"
        >
          <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
            <div class="min-w-0 pr-2 text-sm font-semibold text-slate-900">
              {{ form.id ? 'Edit Position' : 'New Roster Row' }}
            </div>

            <button
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-300 text-sm leading-none text-slate-700 hover:bg-slate-50"
                @click="closeRowEditor"
            >
              ×
            </button>
          </div>

          <div class="flex-1 overflow-y-auto px-4 py-4">
            <div class="editor-grid grid grid-cols-1 gap-y-2 sm:grid-cols-2 sm:gap-x-5">
              <ModalFieldRow label="Slot Type:" class="sm:col-span-1">
                <div class="field-inline w-full">
                  <select v-model="form.slot_type" class="field-input">
                    <option value="primary">Primary</option>
                    <option value="spare">On-Call</option>
                  </select>
                  <InlineFieldHelp text="Primary rows count toward the required target. On-call rows are backup capacity." />
                </div>
              </ModalFieldRow>

              <ModalFieldRow label="Status:" class="sm:col-span-1">
                <div class="field-inline w-full">
                  <select v-model="form.status" class="field-input">
                    <option value="open">Open</option>
                    <option value="ready">Ready</option>
                    <option value="pending_paperwork">Pending paperwork</option>
                    <option value="open_alternate">Open on-call</option>
                  </select>
                  <InlineFieldHelp text="Manual roster status for this slot. Use Open or Open on-call for empty slots, and Ready or Pending paperwork when a carrier or driver is being worked." />
                </div>
              </ModalFieldRow>

              <ModalFieldRow label="Carrier:" class="sm:col-span-1">
                <div class="field-inline w-full">
                  <input v-model="form.carrier_name" maxlength="25" class="field-input" />
                  <InlineFieldHelp text="Carrier name entered by hand for this roster row. This does not create or link to a carrier record." />
                </div>
              </ModalFieldRow>

              <ModalFieldRow label="Driver Name:" class="sm:col-span-1">
                <div class="field-inline w-full">
                  <input v-model="form.driver_name" maxlength="25" class="field-input" />
                  <InlineFieldHelp text="Driver name entered by hand for this roster row. A driver name can be used only once per job roster." />
                </div>
              </ModalFieldRow>
            </div>

            <div
                v-if="error"
                class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
            >
              {{ error }}
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <div class="flex flex-wrap justify-end gap-2">
              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="resetForm"
              >
                Clear
              </button>

              <button
                  type="button"
                  class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="closeRowEditor"
              >
                Cancel
              </button>

              <button
                  v-if="form.id"
                  type="button"
                  class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                  :disabled="savingRow || deletingRow"
                  @click="deleteCurrentRow"
              >
                Delete
              </button>

              <button
                  type="button"
                  class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50"
                  :disabled="savingRow || deletingRow"
                  @click="saveForm"
              >
                {{ savingRow ? 'Saving...' : (form.id ? 'Update Row' : 'Add Row') }}
              </button>
            </div>
          </div>

          <template v-if="isDesktopRowModal">
            <div
                class="absolute inset-y-0 right-0 z-10 hidden w-2 cursor-ew-resize sm:block"
                @mousedown.prevent="startRowResize('right', $event)"
            ></div>

            <div
                class="absolute inset-x-0 bottom-0 z-10 hidden h-2 cursor-ns-resize sm:block"
                @mousedown.prevent="startRowResize('bottom', $event)"
            ></div>

            <div
                class="absolute bottom-0 right-0 z-20 hidden h-4 w-4 cursor-nwse-resize sm:block"
                title="Resize"
                @mousedown.prevent="startRowResize('corner', $event)"
            >
              <div class="absolute bottom-1 right-1 h-2.5 w-2.5 border-b-2 border-r-2 border-gray-300"></div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
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
const deletingRow = ref(false)
const error = ref('')
const rows = ref([])
const rowModalOpen = ref(false)
const rowModalRef = ref(null)

const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)
const viewportHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 900)
const rowModalWidth = ref(760)
const rowModalHeight = ref(null)
const rowResizeState = ref(null)

const ROW_MODAL_STORAGE_KEY = 'roster-row-modal'
const ROW_MODAL_DEFAULT_WIDTH = 760
const ROW_MODAL_DEFAULT_MIN_WIDTH = 620
const ROW_MODAL_DEFAULT_MIN_HEIGHT = 320
const ROW_MODAL_DEFAULT_HEIGHT = 360
const ROW_MODAL_DEFAULT_MAX_HEIGHT_RATIO = 0.82
const DESKTOP_BREAKPOINT = 640

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

const isDesktopRowModal = computed(() => viewportWidth.value >= DESKTOP_BREAKPOINT)

const rowModalStyle = computed(() => {
  if (!isDesktopRowModal.value) {
    return {
      width: '100%',
      maxWidth: '100%',
      height: 'auto',
      maxHeight: '92vh',
    }
  }

  const maxWidth = Math.max(680, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(360, Math.floor(viewportHeight.value - 48))

  const width = Math.min(Math.max(rowModalWidth.value, ROW_MODAL_DEFAULT_MIN_WIDTH), maxWidth)
  const height = rowModalHeight.value == null
      ? Math.min(
          Math.max(
              ROW_MODAL_DEFAULT_HEIGHT,
              Math.floor(viewportHeight.value * ROW_MODAL_DEFAULT_MAX_HEIGHT_RATIO)
          ),
          maxHeight
      )
      : Math.min(Math.max(rowModalHeight.value, ROW_MODAL_DEFAULT_MIN_HEIGHT), maxHeight)

  return {
    width: `${width}px`,
    maxWidth: `${maxWidth}px`,
    height: `${height}px`,
    maxHeight: `${maxHeight}px`,
  }
})

const jobLabel = computed(() => {
  if (!props.job) return 'Job Roster'
  return `${props.job.job_number || '—'} | ${props.job.title || 'Untitled Job'}`
})

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

const displayStartDate = computed(() => {
  return formatDateDisplay(props.job?.job_start_date || '')
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
        rowModalOpen.value = false
        resetForm()
        await loadRoster()
        return
      }

      if (!value) {
        rowModalOpen.value = false
        resetForm()
        stopRowResize()
      }
    },
    { immediate: true }
)

watch(
    () => rowModalOpen.value,
    async (value) => {
      if (!value) {
        stopRowResize()
        return
      }

      await applyRowModalSizeState()
    }
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

function getRowModalStorageKey() {
  return `base-admin-modal-size:${ROW_MODAL_STORAGE_KEY}`
}

function buildCurrentRowModalSizePayload() {
  return {
    width: rowModalWidth.value,
    height: rowModalHeight.value,
  }
}

function writeRememberedRowModalSize(payload) {
  const storageKey = getRowModalStorageKey()

  if (typeof window === 'undefined') {
    return
  }

  window.localStorage.setItem(storageKey, JSON.stringify(payload))
}

function readRememberedRowModalSize() {
  const storageKey = getRowModalStorageKey()

  if (typeof window === 'undefined') {
    return null
  }

  const raw = window.localStorage.getItem(storageKey)
  if (!raw) {
    return null
  }

  try {
    const payload = JSON.parse(raw)
    return payload && typeof payload === 'object' ? payload : null
  } catch (_error) {
    return null
  }
}

function persistRowModalSize() {
  if (!isDesktopRowModal.value) {
    return
  }

  writeRememberedRowModalSize(buildCurrentRowModalSizePayload())
}

function syncViewport() {
  if (typeof window === 'undefined') {
    return
  }

  viewportWidth.value = window.innerWidth
  viewportHeight.value = window.innerHeight

  if (!isDesktopRowModal.value) {
    rowModalHeight.value = null
    return
  }

  const maxWidth = Math.max(680, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(360, Math.floor(viewportHeight.value - 48))

  rowModalWidth.value = Math.min(Math.max(rowModalWidth.value, ROW_MODAL_DEFAULT_MIN_WIDTH), maxWidth)

  if (rowModalHeight.value != null) {
    rowModalHeight.value = Math.min(Math.max(rowModalHeight.value, ROW_MODAL_DEFAULT_MIN_HEIGHT), maxHeight)
  }
}

function resetRowModalSize() {
  rowModalWidth.value = Math.min(
      ROW_MODAL_DEFAULT_WIDTH,
      Math.max(680, Math.floor(viewportWidth.value - 48))
  )

  if (!isDesktopRowModal.value) {
    rowModalHeight.value = null
    return
  }

  const maxHeight = Math.max(360, Math.floor(viewportHeight.value - 48))
  rowModalHeight.value = Math.min(
      Math.max(
          ROW_MODAL_DEFAULT_HEIGHT,
          Math.floor(viewportHeight.value * ROW_MODAL_DEFAULT_MAX_HEIGHT_RATIO)
      ),
      maxHeight
  )
}

async function applyRowModalSizeState() {
  await nextTick()
  syncViewport()

  if (!isDesktopRowModal.value) {
    return
  }

  const remembered = readRememberedRowModalSize()
  if (remembered && typeof remembered === 'object') {
    const maxWidth = Math.max(680, Math.floor(viewportWidth.value - 48))
    const maxHeight = Math.max(360, Math.floor(viewportHeight.value - 48))

    rowModalWidth.value = Math.min(
        Math.max(Number(remembered.width) || ROW_MODAL_DEFAULT_WIDTH, ROW_MODAL_DEFAULT_MIN_WIDTH),
        maxWidth
    )

    if (remembered.height != null) {
      rowModalHeight.value = Math.min(
          Math.max(Number(remembered.height) || ROW_MODAL_DEFAULT_HEIGHT, ROW_MODAL_DEFAULT_MIN_HEIGHT),
          maxHeight
      )
    } else {
      rowModalHeight.value = null
    }

    return
  }

  resetRowModalSize()
}

function onViewportResize() {
  syncViewport()
  persistRowModalSize()
}

onMounted(() => {
  if (typeof window === 'undefined') {
    return
  }

  syncViewport()
  window.addEventListener('resize', onViewportResize)
})

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', onViewportResize)
    window.removeEventListener('mousemove', onRowResizeMove)
    window.removeEventListener('mouseup', stopRowResize)
  }
})

function startRowResize(direction, event) {
  if (!isDesktopRowModal.value) {
    return
  }

  rowResizeState.value = {
    direction,
    startX: event.clientX,
    startY: event.clientY,
    startWidth: rowModalWidth.value,
    startHeight: rowModalHeight.value == null
        ? Math.min(
            Math.max(
                ROW_MODAL_DEFAULT_HEIGHT,
                Math.floor(viewportHeight.value * ROW_MODAL_DEFAULT_MAX_HEIGHT_RATIO)
            ),
            Math.max(360, Math.floor(viewportHeight.value - 48))
        )
        : rowModalHeight.value,
  }

  window.addEventListener('mousemove', onRowResizeMove)
  window.addEventListener('mouseup', stopRowResize)
}

function onRowResizeMove(event) {
  if (!rowResizeState.value) {
    return
  }

  const maxWidth = Math.max(680, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(360, Math.floor(viewportHeight.value - 48))

  const nextWidth = rowResizeState.value.startWidth + (event.clientX - rowResizeState.value.startX)
  const nextHeight = rowResizeState.value.startHeight + (event.clientY - rowResizeState.value.startY)

  if (rowResizeState.value.direction === 'right' || rowResizeState.value.direction === 'corner') {
    rowModalWidth.value = Math.min(Math.max(nextWidth, ROW_MODAL_DEFAULT_MIN_WIDTH), maxWidth)
  }

  if (rowResizeState.value.direction === 'bottom' || rowResizeState.value.direction === 'corner') {
    rowModalHeight.value = Math.min(Math.max(nextHeight, ROW_MODAL_DEFAULT_MIN_HEIGHT), maxHeight)
  }

  persistRowModalSize()
}

function stopRowResize() {
  if (!rowResizeState.value) {
    return
  }

  rowResizeState.value = null
  persistRowModalSize()

  window.removeEventListener('mousemove', onRowResizeMove)
  window.removeEventListener('mouseup', stopRowResize)
}

function startCreate() {
  resetForm()
  rowModalOpen.value = true
}

function closeRowEditor() {
  rowModalOpen.value = false
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
  rowModalOpen.value = true
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

function extractErrorMessage(errorLike) {
  const payload = errorLike?.response?.data
  const validation = payload?.errors

  if (validation && typeof validation === 'object') {
    const firstKey = Object.keys(validation)[0]
    const firstMessage = Array.isArray(validation[firstKey]) ? validation[firstKey][0] : validation[firstKey]
    if (firstMessage) {
      return String(firstMessage)
    }
  }

  return payload?.message || errorLike?.message || 'Something went wrong.'
}

async function saveForm() {
  const duplicate = duplicateDriverRow()
  if (duplicate) {
    const slot = duplicate?.slot_label || duplicate?.slot_type || 'another row'
    error.value = `This driver is already used in ${slot}.`
    return
  }

  savingRow.value = true
  error.value = ''

  try {
    const payload = {
      slot_type: form.slot_type,
      carrier_name: form.carrier_name,
      driver_name: form.driver_name,
      status: form.status,
    }

    if (form.id) {
      await updateJobAssignment(form.id, payload)
    } else {
      await createJobAssignment(props.job.id, payload)
    }

    await loadRoster()
    emit('updated')
    closeRowEditor()
  } catch (e) {
    error.value = extractErrorMessage(e)
  } finally {
    savingRow.value = false
  }
}

async function deleteCurrentRow() {
  if (!form.id) {
    return
  }

  deletingRow.value = true
  error.value = ''

  try {
    await deleteJobAssignment(form.id)
    await loadRoster()
    emit('updated')
    closeRowEditor()
  } catch (e) {
    error.value = extractErrorMessage(e)
  } finally {
    deletingRow.value = false
  }
}
</script>

<style scoped>
.roster-editor-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(15, 23, 42, 0.28);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
}

.roster-editor-card {
  position: relative;
  width: min(760px, calc(100vw - 24px));
  max-width: calc(100vw - 24px);
  max-height: calc(100vh - 24px);
  border-radius: 0.9rem;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.field-inline {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  width: 100%;
  min-width: 0;
}

.field-input {
  width: 100%;
  min-width: 0;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #111827;
  background: #fff;
}

.field-input:focus {
  outline: none;
  border-color: #94a3b8;
}
</style>