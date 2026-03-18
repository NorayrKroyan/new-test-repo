<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Stage' : 'New Stage'"
      :loading="loading"
      :saving="saving"
      :deleting="deleting"
      :record-id="form.id"
      save-label="Save"
      @close="$emit('close')"
      @save="$emit('save')"
      @delete="$emit('delete')"
  >
    <div class="space-y-4">
      <div
          v-if="error"
          class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ error }}
      </div>

      <div class="rounded-xl border border-gray-200 p-4">
        <div class="mb-2 text-sm font-semibold text-gray-900">Stage Info</div>

        <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
          <ModalFieldRow label="Stage Name:" class="md:col-span-1">
            <input
                v-model="form.stage_name"
                class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </ModalFieldRow>

          <ModalFieldRow label="Stage Group:" class="md:col-span-1">
            <select
                v-if="stageGroups.length"
                v-model="form.stage_group"
                class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
            >
              <option value="">Select group</option>
              <option v-for="group in stageGroups" :key="group" :value="group">
                {{ group }}
              </option>
            </select>

            <input
                v-else
                v-model="form.stage_group"
                class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </ModalFieldRow>

          <ModalFieldRow label="Stage Order:" class="md:col-span-1">
            <input
                v-model="form.stage_order"
                type="number"
                min="1"
                class="w-full max-w-[160px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </ModalFieldRow>

          <ModalFieldRow label="Funnel Rule:" class="md:col-span-1">
            <input
                :value="Number(form.stage_order || 0) >= 10 ? 'Dead / not in funnel' : 'Included in funnel'"
                disabled
                class="w-full max-w-[220px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600"
            />
          </ModalFieldRow>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'

defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  saving: Boolean,
  deleting: { type: Boolean, default: false },
  error: {
    type: String,
    default: '',
  },
  form: Object,
  stageGroups: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['close', 'save', 'delete'])
</script>