<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Job' : 'New Job'"
      :loading="loading"
      :saving="saving"
      :deleting="deleting"
      :record-id="form.id"
      save-label="Save"
      @close="$emit('close')"
      @save="$emit('save')"
      @delete="$emit('delete')"
  >
    <div class="rounded-xl border border-gray-200 p-4">
      <div class="mb-2 text-sm font-semibold text-gray-900">Job Info</div>

      <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
        <ModalFieldRow label="Job Number:" class="md:col-span-1">
          <input v-model="form.job_number" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Title:" class="md:col-span-1">
          <input v-model="form.title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Origin City:" class="md:col-span-1">
          <input v-model="form.origin_city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Origin State:" class="md:col-span-1">
          <input v-model="form.origin_state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Destination City:" class="md:col-span-1">
          <input v-model="form.destination_city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Destination State:" class="md:col-span-1">
          <input v-model="form.destination_state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Equipment Type:" class="md:col-span-1">
          <input v-model="form.equipment_type" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Trailer Type:" class="md:col-span-1">
          <input v-model="form.trailer_type" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Weight:" class="md:col-span-1">
          <input v-model="form.weight" type="number" class="w-full max-w-[180px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Rate:" class="md:col-span-1">
          <input v-model="form.rate" type="number" class="w-full max-w-[180px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Status:" class="md:col-span-1">
          <input v-model="form.status" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <div class="hidden md:block md:col-span-1"></div>

        <ModalFieldRow label="Description:" class="md:col-span-2">
          <textarea
              v-model="form.description"
              rows="4"
              class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"
          />
        </ModalFieldRow>
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
  form: Object,
})

defineEmits(['close', 'save', 'delete'])
</script>