<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Carrier' : 'New Carrier'"
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
      <div class="mb-2 text-sm font-semibold text-gray-900">Carrier Info</div>

      <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
        <ModalFieldRow label="Company Name:" class="md:col-span-1">
          <input v-model="form.company_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Contact Name:" class="md:col-span-1">
          <input v-model="form.contact_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Phone:" class="md:col-span-1">
          <input v-model="form.phone" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Email:" class="md:col-span-1">
          <input v-model="form.email" class="w-full max-w-[360px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="City:" class="md:col-span-1">
          <input v-model="form.city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="State:" class="md:col-span-1">
          <input v-model="form.state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="USDOT:" class="md:col-span-1">
          <input v-model="form.usdot" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="MC Number:" class="md:col-span-1">
          <input v-model="form.mc_number" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Carrier Class:" class="md:col-span-1">
          <input v-model="form.carrier_class" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Insurance Status:" class="md:col-span-1">
          <input v-model="form.insurance_status" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Truck Count:" class="md:col-span-1">
          <input v-model="form.truck_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Trailer Count:" class="md:col-span-1">
          <input v-model="form.trailer_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
        </ModalFieldRow>

        <ModalFieldRow label="Status:" class="md:col-span-1">
          <div class="w-full max-w-[220px] rounded-lg">
            <VSelect
                :options="statusOptions"
                :reduce="item => item.value"
                label="label"
                placeholder="Select status"
                v-model="form.status"
            />
          </div>
        </ModalFieldRow>

        <div class="hidden md:block md:col-span-1"></div>

        <ModalFieldRow label="Notes:" class="md:col-span-2">
          <textarea
              v-model="form.notes"
              rows="4"
              class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"
          />
        </ModalFieldRow>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import VSelect from 'vue-select'
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

const statusOptions = [
  { label: 'Pending Review', value: 'pending_review' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]
</script>