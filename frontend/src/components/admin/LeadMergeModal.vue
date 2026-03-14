<template>
  <BaseAdminModal
      :open="open"
      title="Merge Duplicate Leads"
      :loading="loading"
      :saving="saving"
      :deleting="false"
      :record-id="null"
      save-label="Merge Into Survivor"
      @close="$emit('close')"
      @save="submit"
  >
    <div v-if="preview" class="space-y-3">
      <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
        Non-survivor rows will be soft-deleted after merge. Their alt email, alt phone, and other non-surviving values will be appended into the survivor notes log.
      </div>

      <div class="grid gap-2 lg:grid-cols-[1fr_auto_auto_auto]">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
          <div class="font-semibold text-slate-900">Duplicate Group</div>
          <div class="mt-1">Records: {{ leads.length }}</div>
          <div>Match basis: {{ preview.match_basis || 'manual / mixed' }}</div>
          <div>Conflicts: {{ preview.conflict_count }}</div>
        </div>

        <button
            v-for="lead in leads"
            :key="`survivor-${lead.id}`"
            type="button"
            class="rounded-xl border px-3 py-2 text-left text-xs transition"
            :class="lead.id === local.survivor_lead_id
              ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            @click="setSurvivor(lead.id)"
        >
          <div class="font-semibold">Lead #{{ lead.id }}</div>
          <div class="mt-0.5 truncate">{{ lead.full_name || '—' }}</div>
          <div class="mt-1 text-[11px]" :class="lead.id === preview.recommended_survivor_lead_id ? 'text-emerald-700' : 'text-slate-500'">
            {{ lead.id === preview.recommended_survivor_lead_id ? 'Recommended survivor' : 'Use as survivor' }}
          </div>
        </button>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full border-collapse text-xs">
          <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="min-w-[150px] border-b border-slate-200 px-3 py-2 text-left font-semibold">Field</th>
            <th
                v-for="lead in leads"
                :key="`head-${lead.id}`"
                class="min-w-[190px] border-b border-slate-200 px-3 py-2 text-left font-semibold"
            >
              <div class="flex items-center justify-between gap-2">
                <span>Lead #{{ lead.id }}</span>
                <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                    :class="lead.id === local.survivor_lead_id
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-slate-200 text-slate-600'"
                >
                  {{ lead.id === local.survivor_lead_id ? 'Survivor' : 'Source' }}
                </span>
              </div>
            </th>
            <th class="min-w-[170px] border-b border-slate-200 px-3 py-2 text-left font-semibold">Final</th>
          </tr>
          </thead>

          <tbody>
          <tr
              v-for="(field, rowIndex) in fields"
              :key="field.key"
              :class="rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
          >
            <td class="border-b border-slate-200 px-3 py-2 align-top font-semibold text-slate-700">
              {{ field.label }}
            </td>

            <td
                v-for="entry in field.values"
                :key="`${field.key}-${entry.lead_id}`"
                class="border-b border-slate-200 px-3 py-2 align-top"
            >
              <button
                  type="button"
                  class="w-full rounded-lg border px-2 py-2 text-left transition"
                  :class="entry.lead_id === local.selections[field.key]
                    ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200'
                    : 'border-slate-200 bg-white hover:bg-slate-50'"
                  @click="selectField(field.key, entry.lead_id)"
              >
                <div class="line-clamp-3 break-words text-slate-800">{{ entry.value }}</div>
                <div class="mt-2 text-[10px] font-semibold"
                     :class="entry.lead_id === local.selections[field.key] ? 'text-sky-700' : 'text-slate-500'">
                  {{ entry.lead_id === local.selections[field.key] ? 'Selected' : 'Use this value' }}
                </div>
              </button>
            </td>

            <td class="border-b border-slate-200 px-3 py-2 align-top">
              <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2">
                <div class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                  Lead #{{ local.selections[field.key] || local.survivor_lead_id }}
                </div>
                <div class="mt-1 line-clamp-3 break-words text-slate-800">
                  {{ selectedDisplayValue(field) }}
                </div>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="grid gap-3 lg:grid-cols-[1.2fr_1fr]">
        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
          <div class="font-semibold text-slate-900">Final survivor preview</div>
          <div class="mt-2 space-y-1.5">
            <div v-for="field in fields" :key="`final-${field.key}`" class="flex items-start justify-between gap-3">
              <div class="text-slate-500">{{ field.label }}</div>
              <div class="max-w-[60%] text-right font-medium text-slate-800">{{ selectedDisplayValue(field) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'

const props = defineProps({
  open: Boolean,
  preview: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  saving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'save'])

const local = reactive({
  survivor_lead_id: null,
  selections: {},
})

const leads = computed(() => props.preview?.leads || [])
const fields = computed(() => props.preview?.fields || [])

watch(
    () => props.preview,
    (preview) => {
      if (!preview) {
        local.survivor_lead_id = null
        local.selections = {}
        return
      }

      local.survivor_lead_id = preview.recommended_survivor_lead_id || preview?.leads?.[0]?.id || null

      const nextSelections = {}
      for (const field of preview.fields || []) {
        nextSelections[field.key] = field.recommended_source_lead_id || local.survivor_lead_id
      }

      local.selections = nextSelections
    },
    { immediate: true },
)

function setSurvivor(leadId) {
  local.survivor_lead_id = Number(leadId)
}

function selectField(fieldKey, leadId) {
  local.selections = {
    ...local.selections,
    [fieldKey]: Number(leadId),
  }
}

function selectedDisplayValue(field) {
  const selectedLeadId = Number(local.selections[field.key] || local.survivor_lead_id || 0)
  const entry = (field.values || []).find((item) => Number(item.lead_id) === selectedLeadId)
  return entry?.value || '—'
}

function submit() {
  emit('save', {
    survivor_lead_id: Number(local.survivor_lead_id),
    selections: { ...local.selections },
  })
}
</script>
