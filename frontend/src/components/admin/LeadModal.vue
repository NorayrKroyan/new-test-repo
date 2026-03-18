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
    <div class="space-y-3">
      <div
          v-if="form.duplicate_of_lead_id"
          class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
      >
        This lead is marked as a duplicate of lead #{{ form.duplicate_of_lead_id }}
        <span v-if="form.duplicate_basis">({{ duplicateBasisLabel }})</span>.
      </div>

      <div
          :class="[
            'grid grid-cols-1 gap-3',
            isEditMode ? 'xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]' : '',
          ]"
      >
        <div class="rounded-lg border border-gray-200 p-3">
          <div class="mb-2 text-xs font-semibold text-gray-900">Lead Info</div>

          <div class="grid grid-cols-1 gap-y-1.5 md:grid-cols-2 md:gap-x-4">
            <ModalFieldRow label="Source Name:" class="md:col-span-1">
              <input v-model="form.source_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Ad Name:" class="md:col-span-1">
              <input v-model="form.ad_name" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Platform:" class="md:col-span-1">
              <input v-model="form.platform" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Lead Status:" class="md:col-span-1">
              <select v-model="form.lead_status" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="disqualified">Disqualified</option>
                <option value="converted_to_carrier">Converted to Carrier</option>
                <option v-if="form.duplicate_of_lead_id" value="duplicate">Duplicate</option>
              </select>
            </ModalFieldRow>

            <ModalFieldRow label="Stage:" class="md:col-span-1">
              <select v-model="form.lead_stage_id" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                <option value="">—</option>
                <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">
                  {{ stage.stage_name }} / {{ stage.stage_group }} / {{ stage.stage_order }}
                </option>
              </select>
            </ModalFieldRow>

            <ModalFieldRow label="Full Name:" class="md:col-span-1">
              <input v-model="form.full_name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Email:" class="md:col-span-1">
              <input v-model="form.email" class="w-full max-w-[360px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Phone:" class="md:col-span-1">
              <input v-model="form.phone" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Insurance:" class="md:col-span-1">
              <input v-model="form.insurance_answer" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="City:" class="md:col-span-1">
              <input v-model="form.city" class="w-full max-w-[260px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="State:" class="md:col-span-1">
              <input v-model="form.state" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Carrier Class:" class="md:col-span-1">
              <input v-model="form.carrier_class" class="w-full max-w-[320px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="USDOT:" class="md:col-span-1">
              <input v-model="form.usdot" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Truck Count:" class="md:col-span-1">
              <input v-model="form.truck_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Trailer Count:" class="md:col-span-1">
              <input v-model="form.trailer_count" type="number" class="w-full max-w-[140px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow label="Start Date Choice:" class="md:col-span-1">
              <input v-model="form.lead_date_choice" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
            </ModalFieldRow>

            <ModalFieldRow v-if="form.duplicate_of_lead_id" label="Duplicate Of Lead ID:" class="md:col-span-1">
              <input :value="form.duplicate_of_lead_id" disabled class="w-full max-w-[140px] rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-600" />
            </ModalFieldRow>

            <ModalFieldRow label="Notes:" class="md:col-span-2">
              <textarea
                  v-model="form.notes"
                  rows="5"
                  class="min-h-[120px] max-h-[420px] w-full resize-y overflow-auto rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
              />
            </ModalFieldRow>
          </div>
        </div>

        <div v-if="isEditMode" class="rounded-lg border border-gray-200 p-3">
          <div class="mb-2 flex items-center justify-between gap-2">
            <div class="text-xs font-semibold text-gray-900">Qualification</div>

            <span
                v-if="qualificationSession?.recommended_status"
                class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700"
            >
              {{ formatStatus(qualificationSession.recommended_status) }}
            </span>
          </div>

          <div v-if="qualificationLoading" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
            Loading qualification...
          </div>

          <div v-else-if="qualificationError" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-2 text-xs text-rose-700">
            {{ qualificationError }}
          </div>

          <div v-else-if="!qualificationSession" class="space-y-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
              Qualification session is not loaded.
            </div>

            <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="$emit('qualify')"
            >
              Load Qualification
            </button>
          </div>

          <div v-else class="space-y-3">
            <div
                v-if="hasRecommendation"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-2 text-xs text-emerald-800"
            >
              <div v-if="recommendedStageLabel">
                <span class="font-semibold">Recommended stage:</span>
                {{ recommendedStageLabel }}
              </div>

              <div v-if="qualificationSession?.recommended_status">
                <span class="font-semibold">Recommended status:</span>
                {{ formatStatus(qualificationSession.recommended_status) }}
              </div>
            </div>

            <div
                v-if="hideQuestionnaire"
                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600"
            >
              This lead already has a finalized qualification result. The questionnaire is hidden, and saved answers remain visible below.
            </div>

            <template v-else>
              <div
                  v-if="currentStep"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-2.5"
              >
                <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                  Current Step
                </div>

                <div class="text-xs font-semibold text-slate-900">
                  {{ currentStepTitle }}
                </div>

                <div
                    v-if="currentStepHelpText"
                    class="mt-1 text-xs leading-4 text-slate-600"
                >
                  {{ currentStepHelpText }}
                </div>

                <div class="mt-2.5 space-y-2">
                  <div v-if="currentStepOptions.length">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Option
                    </label>

                    <select
                        v-model="qualificationForm.option_id"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    >
                      <option value="">Select option</option>
                      <option
                          v-for="option in currentStepOptions"
                          :key="option.id"
                          :value="String(option.id)"
                      >
                        {{ optionLabel(option) }}
                      </option>
                    </select>
                  </div>

                  <div v-if="showAnswerValueInput">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Answer Value
                    </label>

                    <input
                        v-model="qualificationForm.answer_value"
                        :type="answerValueInputType"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>

                  <div v-if="showAnswerTextInput">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Answer Text
                    </label>

                    <textarea
                        v-model="qualificationForm.answer_text"
                        rows="2"
                        class="w-full resize-none rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>

                  <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                      Note
                    </label>

                    <textarea
                        v-model="qualificationForm.note"
                        rows="2"
                        class="w-full resize-none rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs"
                    />
                  </div>
                </div>
              </div>

              <div
                  v-else
                  class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs text-slate-600"
              >
                No current qualification step available.
              </div>

              <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button
                    v-if="currentStep"
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || !qualificationForm.step_id"
                    @click="$emit('save-answer')"
                >
                  {{ qualificationSaving ? 'Saving...' : 'Save Progress' }}
                </button>

                <button
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || qualificationSession?.status === 'completed' || qualificationSession?.status === 'disqualified'"
                    @click="$emit('complete-qualification')"
                >
                  Finish Session
                </button>

                <button
                    type="button"
                    class="w-full whitespace-nowrap rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 sm:w-auto"
                    :disabled="qualificationSaving || qualificationApplying || !hasRecommendation"
                    @click="$emit('apply-recommended-stage')"
                >
                  {{ qualificationApplying ? 'Applying...' : 'Apply to Lead' }}
                </button>
              </div>
            </template>

            <div v-if="displayedAnswers.length" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2.5">
              <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ displayedAnswersTitle }}
              </div>

              <div class="space-y-2">
                <div
                    v-for="answer in displayedAnswers"
                    :key="answer.id"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs"
                >
                  <div class="font-medium text-slate-900">
                    {{ answerStepLabel(answer) }}
                  </div>

                  <div v-if="answer.option?.option_label || answer.option?.label" class="text-slate-700">
                    Option: {{ answer.option?.option_label || answer.option?.label }}
                  </div>

                  <div v-if="answer.answer_value !== null && answer.answer_value !== ''" class="text-slate-700">
                    Value: {{ answer.answer_value }}
                  </div>

                  <div v-if="answer.answer_text" class="text-slate-700">
                    Text: {{ answer.answer_text }}
                  </div>
                </div>
              </div>
            </div>
          </div>
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
  form: {
    type: Object,
    default: () => ({}),
  },
  stages: {
    type: Array,
    default: () => [],
  },
  qualificationLoading: {
    type: Boolean,
    default: false,
  },
  qualificationSaving: {
    type: Boolean,
    default: false,
  },
  qualificationApplying: {
    type: Boolean,
    default: false,
  },
  qualificationSession: {
    type: Object,
    default: null,
  },
  qualificationHistorySession: {
    type: Object,
    default: null,
  },
  qualificationForm: {
    type: Object,
    default: () => ({
      step_id: null,
      option_id: '',
      answer_value: '',
      answer_text: '',
      note: '',
    }),
  },
  qualificationError: {
    type: String,
    default: '',
  },
})

const isEditMode = computed(() => Boolean(props.form?.id))

const duplicateBasisLabel = computed(() => {
  const value = String(props.form?.duplicate_basis ?? '').trim().toLowerCase()

  if (value === 'phone') return 'matched by phone'
  if (value === 'email') return 'matched by email'
  if (value === 'manual') return 'marked manually'
  if (value === 'merged') return 'merged'

  return value || 'duplicate'
})

const normalizedLeadStatus = computed(() => {
  return String(props.form?.lead_status ?? '').trim().toLowerCase()
})

const hideQuestionnaire = computed(() => {
  return ['qualified', 'disqualified', 'converted_to_carrier'].includes(normalizedLeadStatus.value)
})

const currentStep = computed(() => props.qualificationSession?.current_step ?? null)

const currentStepOptions = computed(() => {
  return Array.isArray(currentStep.value?.options) ? currentStep.value.options : []
})

const currentStepInputType = computed(() => {
  return String(
      currentStep.value?.input_type ??
      currentStep.value?.answer_input_type ??
      ''
  ).trim().toLowerCase()
})

const currentStepTitle = computed(() => {
  return (
      currentStep.value?.question_text ||
      currentStep.value?.question ||
      currentStep.value?.title ||
      currentStep.value?.step_label ||
      currentStep.value?.label ||
      currentStep.value?.step_key ||
      'Qualification Step'
  )
})

const currentStepHelpText = computed(() => {
  return (
      currentStep.value?.help_text ||
      currentStep.value?.description ||
      currentStep.value?.instructions ||
      ''
  )
})

const showAnswerValueInput = computed(() => {
  if (!currentStep.value) return false
  if (currentStepOptions.value.length > 0) return false

  return ['number', 'numeric', 'integer', 'decimal', 'float', 'text', 'short_text', 'string', ''].includes(currentStepInputType.value)
})

const showAnswerTextInput = computed(() => {
  if (!currentStep.value) return false
  if (currentStepOptions.value.length > 0) return false

  return ['textarea', 'text_area', 'long_text', 'note', 'notes'].includes(currentStepInputType.value)
})

const answerValueInputType = computed(() => {
  return ['number', 'numeric', 'integer', 'decimal', 'float'].includes(currentStepInputType.value)
      ? 'number'
      : 'text'
})

const recommendedStageLabel = computed(() => {
  const stage = props.qualificationSession?.recommended_stage

  if (stage?.stage_name) {
    return stage.stage_group ? `${stage.stage_name} (${stage.stage_group})` : stage.stage_name
  }

  return props.qualificationSession?.recommended_stage_name || props.qualificationSession?.recommended_stage_label || ''
})

const hasRecommendation = computed(() => {
  return Boolean(recommendedStageLabel.value || props.qualificationSession?.recommended_status)
})

const sessionAnswers = computed(() => {
  return Array.isArray(props.qualificationSession?.answers) ? props.qualificationSession.answers : []
})

const historyAnswers = computed(() => {
  return Array.isArray(props.qualificationHistorySession?.answers) ? props.qualificationHistorySession.answers : []
})

const displayedAnswers = computed(() => {
  return sessionAnswers.value.length ? sessionAnswers.value : historyAnswers.value
})

const displayedAnswersTitle = computed(() => {
  return sessionAnswers.value.length ? 'Saved Answers' : 'Previous Answers'
})

function optionLabel(option) {
  return (
      option?.option_label ||
      option?.label ||
      option?.option_value ||
      option?.value ||
      `Option #${option?.id ?? ''}`
  )
}

function formatStatus(value) {
  const normalized = String(value ?? '').trim().toLowerCase()

  if (normalized.includes('disqual')) {
    return 'Disqualified'
  }

  if (normalized === 'ready_for_senior_rep') {
    return 'Qualified'
  }

  return String(value ?? '')
      .trim()
      .replaceAll('_', ' ')
      .replace(/\s+/g, ' ')
      .replace(/\b\w/g, (char) => char.toUpperCase())
}

function answerStepLabel(answer) {
  return (
      answer?.step?.question_text ||
      answer?.step?.question ||
      answer?.step?.title ||
      answer?.step?.step_label ||
      answer?.step?.label ||
      answer?.step?.step_key ||
      `Step #${answer?.step_id ?? ''}`
  )
}

defineEmits([
  'close',
  'save',
  'delete',
  'qualify',
  'save-answer',
  'complete-qualification',
  'apply-recommended-stage',
])
</script>