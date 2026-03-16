<template>
  <BaseAdminModal
      :open="open"
      :title="form.id ? 'Edit Lead' : 'New Lead'"
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
          v-if="form.duplicate_of_lead_id"
          class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
      >
        This lead is marked as a duplicate of lead #{{ form.duplicate_of_lead_id }}
        <span v-if="form.duplicate_basis">({{ duplicateBasisLabel }})</span>.
      </div>

      <div class="rounded-xl border border-gray-200 p-4">
        <div class="mb-2 text-sm font-semibold text-gray-900">Lead Info</div>

        <div class="grid grid-cols-1 gap-y-2 md:grid-cols-2 md:gap-x-6">
          <ModalFieldRow label="Source Name:" class="md:col-span-1">
            <input v-model="form.source_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Ad Name:" class="md:col-span-1">
            <input v-model="form.ad_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Platform:" class="md:col-span-1">
            <input v-model="form.platform" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Lead Status:" class="md:col-span-1">
            <select v-model="form.lead_status" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="qualified">Qualified</option>
              <option value="converted_to_carrier">Converted to Carrier</option>
              <option v-if="form.duplicate_of_lead_id" value="duplicate">Duplicate</option>
            </select>
          </ModalFieldRow>

          <ModalFieldRow label="Stage:" class="md:col-span-1">
            <select v-model="form.lead_stage_id" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm">
              <option value="">—</option>
              <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">
                {{ stage.stage_name }} / {{ stage.stage_group }} / {{ stage.stage_order }}
              </option>
            </select>
          </ModalFieldRow>

          <ModalFieldRow label="Full Name:" class="md:col-span-1">
            <input v-model="form.full_name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Email:" class="md:col-span-1">
            <input v-model="form.email" class="w-full max-w-[360px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Phone:" class="md:col-span-1">
            <input v-model="form.phone" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Insurance:" class="md:col-span-1">
            <input v-model="form.insurance_answer" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="City:" class="md:col-span-1">
            <input v-model="form.city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="State:" class="md:col-span-1">
            <input v-model="form.state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Carrier Class:" class="md:col-span-1">
            <input v-model="form.carrier_class" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="USDOT:" class="md:col-span-1">
            <input v-model="form.usdot" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Truck Count:" class="md:col-span-1">
            <input v-model="form.truck_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Trailer Count:" class="md:col-span-1">
            <input v-model="form.trailer_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow label="Start Date Choice:" class="md:col-span-1">
            <input v-model="form.lead_date_choice" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm" />
          </ModalFieldRow>

          <ModalFieldRow v-if="form.duplicate_of_lead_id" label="Duplicate Of Lead ID:" class="md:col-span-1">
            <input :value="form.duplicate_of_lead_id" disabled class="w-full max-w-[140px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600" />
          </ModalFieldRow>

          <ModalFieldRow label="Notes:" class="md:col-span-2">
            <textarea
                v-model="form.notes"
                rows="4"
                class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </ModalFieldRow>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'

const props = defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  saving: Boolean,
  deleting: { type: Boolean, default: false },
  form: Object,
  stages: {
    type: Array,
    default: () => [],
  },
})

const duplicateBasisLabel = computed(() => {
  const value = String(props.form?.duplicate_basis ?? '').trim().toLowerCase()

  if (value === 'phone') return 'matched by phone'
  if (value === 'email') return 'matched by email'
  if (value === 'manual') return 'marked manually'

  return value || 'duplicate'
})

defineEmits(['close', 'save', 'delete'])
</script>