<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Job' : 'New Job'"
      :loading="loading"
      :saving="saving"
      :deleting="deleting"
      :record-id="form.id"
      size-memory-key="job-available-modal"
      :default-width="1120"
      :default-height="860"
      :min-width="980"
      :min-height="720"
      save-label="Save"
      @close="$emit('close')"
      @save="$emit('save')"
      @delete="$emit('delete')"
  >
    <div class="space-y-2 overflow-x-hidden">
      <div class="rounded-lg border border-gray-200 bg-white p-2.5">
        <div class="mb-1.5 text-[11px] font-semibold text-gray-900">Job Info</div>

        <div class="grid min-w-0 grid-cols-1 gap-y-1 md:grid-cols-2 md:gap-x-3">
          <ModalFieldRow label="Job Number:" class="md:col-span-1">
            <div class="field-inline max-w-[280px]">
              <input v-model="form.job_number" class="field-input" />
              <InlineFieldHelp text="Short internal reference shown in the jobs list and dashboard tiles." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Title:" class="md:col-span-1">
            <div class="field-inline">
              <input v-model="form.title" class="field-input" />
              <InlineFieldHelp text="Main job or project name visible throughout the admin flow." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Job Start Date:" class="md:col-span-1">
            <div class="field-inline max-w-[200px]">
              <input v-model="form.job_start_date" type="date" class="field-input" />
              <InlineFieldHelp text="First planned operating date for the job and the main date shown in roster snapshots." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Status:" class="md:col-span-1">
            <div class="field-inline max-w-[200px]">
              <select v-model="form.status" class="field-input">
                <option value="open">Open</option>
                <option value="filled">Filled</option>
                <option value="closed">Closed</option>
              </select>
              <InlineFieldHelp text="Overall job state shown on the jobs page. Open jobs remain active for roster management." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Origin City:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <input v-model="form.origin_city" class="field-input" />
              <InlineFieldHelp text="Route origin city used for display and job context." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Origin State:" class="md:col-span-1">
            <div class="field-inline max-w-[140px]">
              <input v-model="form.origin_state" class="field-input" />
              <InlineFieldHelp text="Route origin state or region used for display and job context." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Destination City:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <input v-model="form.destination_city" class="field-input" />
              <InlineFieldHelp text="Route destination city used for display and job context." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Destination State:" class="md:col-span-1">
            <div class="field-inline max-w-[140px]">
              <input v-model="form.destination_state" class="field-input" />
              <InlineFieldHelp text="Route destination state or region used for display and job context." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Equipment Type:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <input v-model="form.equipment_type" class="field-input" />
              <InlineFieldHelp text="Required truck or equipment type for the job." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Trailer Type:" class="md:col-span-1">
            <div class="field-inline max-w-[220px]">
              <input v-model="form.trailer_type" class="field-input" />
              <InlineFieldHelp text="Preferred or required trailer style for the job." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Weight:" class="md:col-span-1">
            <div class="field-inline max-w-[150px]">
              <input v-model="form.weight" type="number" class="field-input" />
              <InlineFieldHelp text="Optional operating or shipment weight reference for the job." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Primary Required:" class="md:col-span-1">
            <div class="field-inline max-w-[150px]">
              <input v-model="form.primary_required" type="number" min="0" class="field-input" />
              <InlineFieldHelp text="Number of live trucks needed for the job to be fully staffed." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="On-Call Allowed:" class="md:col-span-1">
            <div class="field-inline max-w-[150px]">
              <input v-model="form.spare_allowed" type="number" min="0" class="field-input" />
              <InlineFieldHelp text="Additional on-call rows allowed beyond the primary requirement." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Rate Description:" class="md:col-span-2">
            <div class="field-inline">
              <textarea v-model="form.rate_description" rows="2" class="field-textarea" />
              <InlineFieldHelp text="Free-text pay details, commercial terms, or job notes." />
            </div>
          </ModalFieldRow>

          <ModalFieldRow label="Description:" class="md:col-span-2">
            <div class="field-inline">
              <textarea v-model="form.description" rows="3" class="field-textarea" />
              <InlineFieldHelp text="General dispatch, operational, or customer notes for the overall job header." />
            </div>
          </ModalFieldRow>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] text-slate-700">
        <div class="font-semibold text-slate-900">Roster</div>
        <div class="mt-0.5">
          After the job is saved, use <span class="font-semibold">Manage Roster</span>
          to manage the manual primary and on-call roster slots.
        </div>

        <div v-if="form.id" class="mt-1.5 flex flex-wrap items-center gap-1.5">
          <div
              class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
              :class="summaryClass"
          >
            {{ summaryText }}
          </div>

          <button
              type="button"
              class="rounded-lg border border-slate-300 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
              @click="$emit('manage-roster')"
          >
            Manage Roster
          </button>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'
import InlineFieldHelp from './InlineFieldHelp.vue'

const props = defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  saving: Boolean,
  deleting: { type: Boolean, default: false },
  form: {
    type: Object,
    default: () => ({}),
  },
  rosterSummary: {
    type: Object,
    default: () => ({
      primary_required: 0,
      primary_filled: 0,
      spare_allowed: 0,
      spare_filled: 0,
      primary_overfill: 0,
      spare_overfill: 0,
    }),
  },
})

defineEmits(['close', 'save', 'delete', 'manage-roster'])

const summaryText = computed(() => {
  const summary = props.rosterSummary || {}
  return `Roster: ${summary.primary_filled || 0} / ${summary.primary_required || 0} + ${summary.spare_filled || 0}`
})

const summaryClass = computed(() => {
  const summary = props.rosterSummary || {}

  if ((summary.primary_overfill || 0) > 0 || (summary.spare_overfill || 0) > 0) {
    return 'border-rose-200 bg-rose-50 text-rose-700'
  }

  if ((summary.primary_filled || 0) >= (summary.primary_required || 0) && (summary.primary_required || 0) > 0) {
    return 'border-amber-200 bg-amber-50 text-amber-700'
  }

  return 'border-sky-200 bg-sky-50 text-sky-700'
})
</script>

<style scoped>
.field-inline {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  width: 100%;
  min-width: 0;
}

.field-input,
.field-textarea {
  width: 100%;
  min-width: 0;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.28125rem 0.5rem;
  font-size: 0.6875rem;
  line-height: 1.05rem;
  color: #111827;
  background: #fff;
}

.field-input:focus,
.field-textarea:focus {
  outline: none;
  border-color: #94a3b8;
}

.field-textarea {
  resize: none;
}
</style>