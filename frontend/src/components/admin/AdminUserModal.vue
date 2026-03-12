<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Admin User' : 'New Admin User'"
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
      <div class="mb-2 text-sm font-semibold text-gray-900">Admin User Info</div>

      <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
        <ModalFieldRow label="Name:" class="md:col-span-1">
          <input
              v-model="form.name"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
          />
        </ModalFieldRow>

        <ModalFieldRow label="Email:" class="md:col-span-1">
          <input
              v-model="form.email"
              type="email"
              class="w-full max-w-[360px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
          />
        </ModalFieldRow>

        <ModalFieldRow :label="form.id ? 'Password:' : 'Password:'" class="md:col-span-1">
          <input
              v-model="form.password"
              type="password"
              class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
              :placeholder="form.id ? 'Leave blank to keep existing' : ''"
          />
        </ModalFieldRow>

        <div class="hidden md:block md:col-span-1"></div>

        <ModalFieldRow label="Active:" class="md:col-span-1 -mt-1">
          <div class="w-full max-w-[220px]">
            <input
                id="admin-active"
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300"
            />
          </div>
        </ModalFieldRow>

        <div class="hidden md:block md:col-span-1"></div>
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