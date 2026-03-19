<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Qualification Script' : 'New Qualification Script'"
      :saving="saving"
      :deleting="deleting"
      :record-id="form.id"
      save-label="Save"
      @close="$emit('close')"
      @save="$emit('save')"
      @delete="$emit('delete')"
  >
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
      <ModalFieldRow label="Name:" class="md:col-span-2">
        <input v-model="form.name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Slug:" class="md:col-span-1">
        <input v-model="form.slug" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Version:" class="md:col-span-1">
        <input v-model="form.version" type="number" min="1" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Priority:" class="md:col-span-1">
        <input v-model="form.priority" type="number" min="0" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Platform:" class="md:col-span-1">
        <input v-model="form.applies_to_platform" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Ad Name:" class="md:col-span-2">
        <input v-model="form.applies_to_ad_name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
      </ModalFieldRow>

      <ModalFieldRow label="Description:" class="md:col-span-2">
        <textarea
            v-model="form.description"
            rows="4"
            class="w-full resize-y rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
        />
      </ModalFieldRow>

      <ModalFieldRow label="Active:" class="md:col-span-1">
        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
          <input v-model="form.is_active" type="checkbox" />
          Active script
        </label>
      </ModalFieldRow>

      <ModalFieldRow label="Default:" class="md:col-span-1">
        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
          <input v-model="form.is_default" type="checkbox" />
          Use as default fallback
        </label>
      </ModalFieldRow>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'

defineProps({
  open: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  deleting: { type: Boolean, default: false },
  form: {
    type: Object,
    default: () => ({}),
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['close', 'save', 'delete'])
</script>
