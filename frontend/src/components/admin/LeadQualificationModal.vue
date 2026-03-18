<template>
  <BaseAdminModal
      :open="open"
      :title="session?.id ? `Qualification Call #${session.id}` : 'Qualification Call'"
      :loading="loading"
      :saving="saving"
      :deleting="false"
      :record-id="session?.id || null"
      save-label="Save Answer"
      @close="$emit('close')"
      @save="$emit('save-answer')"
  >
    <div class="space-y-4">
      <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ error }}
      </div>

      <div class="rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-1 gap-y-2 text-sm md:grid-cols-2 md:gap-x-6">
          <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lead</div>
            <div class="mt-1 text-gray-900">{{ session?.lead?.full_name || '—' }}</div>
          </div>

          <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Script</div>
            <div class="mt-1 text-gray-900">{{ session?.script?.name || '—' }}</div>
          </div>

          <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Result</div>
            <div class="mt-1 text-gray-900">{{ session?.recommended_status || session?.status || '—' }}</div>
          </div>

          <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recommended Stage</div>
            <div class="mt-1 text-gray-900">{{ session?.recommended_stage_label || '—' }}</div>
          </div>
        </div>
      </div>

      <div v-if="session?.current_step" class="rounded-xl border border-gray-200 p-4">
        <div class="mb-1 text-sm font-semibold text-gray-900">{{ session.current_step.title }}</div>
        <div class="text-sm leading-6 text-gray-700">{{ session.current_step.prompt_text }}</div>

        <div v-if="session.current_step.help_text" class="mt-2 text-xs text-gray-500">
          {{ session.current_step.help_text }}
        </div>

        <div class="mt-4 space-y-3">
          <template v-if="session.current_step.step_type === 'single_select'">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Answer</label>
              <select
                  v-model="form.option_id"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              >
                <option value="">— Select —</option>
                <option
                    v-for="option in session.current_step.options || []"
                    :key="option.id"
                    :value="String(option.id)"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
          </template>

          <template v-else-if="session.current_step.step_type === 'number'">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Answer</label>
              <input
                  v-model="form.answer_value"
                  type="number"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              />
            </div>
          </template>

          <template v-else>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Answer</label>
              <textarea
                  v-model="form.answer_text"
                  rows="3"
                  class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"
              />
            </div>
          </template>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Rep Note</label>
            <textarea
                v-model="form.note"
                rows="3"
                class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
        </div>
      </div>

      <div v-else class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        This session has no more remaining prompts. Review the recommendation below and complete/apply it.
      </div>

      <div v-if="session?.answers?.length" class="rounded-xl border border-gray-200 p-4">
        <div class="mb-2 text-sm font-semibold text-gray-900">Call History</div>

        <div class="space-y-2">
          <div
              v-for="answer in session.answers"
              :key="answer.id"
              class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2"
          >
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
              {{ answer.step_key_snapshot }}
            </div>
            <div class="mt-1 text-sm text-gray-900">{{ answer.prompt_snapshot }}</div>
            <div class="mt-1 text-sm text-gray-700">
              <span class="font-medium">Answer:</span>
              {{ answer.answer_label || answer.answer_value || answer.answer_text || '—' }}
            </div>
            <div v-if="answer.answer_text && answer.answer_label" class="mt-1 text-sm text-gray-700">
              <span class="font-medium">Note:</span>
              {{ answer.answer_text }}
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            :disabled="saving || !session?.id"
            @click="$emit('complete')"
        >
          Complete Session
        </button>

        <button
            type="button"
            class="rounded-xl border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-60"
            :disabled="applying || !session?.recommended_stage_id"
            @click="$emit('apply-stage')"
        >
          {{ applying ? 'Applying...' : 'Apply Recommended Stage' }}
        </button>
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import BaseAdminModal from './BaseAdminModal.vue'

defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  applying: { type: Boolean, default: false },
  session: {
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

defineEmits(['close', 'save-answer', 'complete', 'apply-stage'])
</script>