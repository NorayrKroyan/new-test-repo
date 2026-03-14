<template>
  <BaseAdminModal
      :open="open"
      title="Mark Duplicate"
      :loading="false"
      :saving="saving"
      :deleting="false"
      :record-id="null"
      save-label="Mark Duplicate"
      @close="$emit('close')"
      @save="$emit('save')"
  >
    <div class="space-y-3">
      <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
        <div><span class="font-semibold">Lead:</span> #{{ lead?.id || '—' }}</div>
        <div><span class="font-semibold">Name:</span> {{ lead?.full_name || '—' }}</div>
        <div><span class="font-semibold">Email:</span> {{ lead?.email || '—' }}</div>
      </div>

      <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
        Enter the master lead ID that should survive. This row will be marked as a duplicate of that lead.
      </div>

      <div
          v-if="error"
          class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
      >
        {{ error }}
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Master Lead ID</label>
        <input
            v-model="form.master_lead_id"
            type="number"
            min="1"
            class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-slate-400"
            placeholder="Enter master lead ID"
            @keydown.enter="$emit('save')"
        />
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import BaseAdminModal from './BaseAdminModal.vue'

defineProps({
  open: Boolean,
  saving: {
    type: Boolean,
    default: false,
  },
  lead: {
    type: Object,
    default: null,
  },
  form: {
    type: Object,
    required: true,
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['close', 'save'])
</script>
