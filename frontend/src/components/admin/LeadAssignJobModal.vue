<template>
  <BaseAdminModal
      :open="open"
      title="Assign Lead To Job"
      :saving="false"
      :record-id="null"
      save-label="Close"
      size-memory-key="lead-assign-job-modal"
      @close="$emit('close')"
      @save="$emit('close')"
      @delete="noop"
  >
    <div class="space-y-3">
      <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
        <div class="font-semibold text-amber-900">{{ leadName || 'Lead' }}</div>
        <div class="mt-1">
          Job roster assignment is temporarily manual-only.
          Qualified leads should not be assigned directly into roster rows from the lead flow right now.
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
        <div class="font-semibold text-slate-900">Current Temporary Rule</div>
        <div class="mt-1">
          Manage roster rows from the Job screen only. Each roster row is a manual entry with carrier, driver, and status,
          and is not linked to leads, carriers, or drivers.
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import BaseAdminModal from './BaseAdminModal.vue'

defineProps({
  open: { type: Boolean, default: false },
  leadName: {
    type: String,
    default: '',
  },
})

defineEmits(['close'])

function noop() {}
</script>