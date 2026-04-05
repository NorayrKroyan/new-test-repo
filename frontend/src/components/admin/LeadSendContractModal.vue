<template>
  <BaseAdminModal
    :open="open"
    title="Send Contract"
    :loading="loading"
    :saving="sending"
    save-label="Send"
    @close="$emit('close')"
    @save="$emit('send')"
  >
    <div class="space-y-3">
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <div class="grid grid-cols-1 gap-y-1.5 md:grid-cols-2 md:gap-x-4">
          <ModalFieldRow label="Recipient Name:" class="md:col-span-1">
            <input v-model="localForm.recipient_name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
          </ModalFieldRow>

          <ModalFieldRow label="Recipient Email:" class="md:col-span-1">
            <input v-model="localForm.recipient_email" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
          </ModalFieldRow>

          <ModalFieldRow label="Source Type:" class="md:col-span-1">
            <select v-model="localForm.source_type" class="w-full max-w-[220px] rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
              <option value="template">Existing Contract</option>
              <option value="upload">Upload File</option>
            </select>
          </ModalFieldRow>

          <ModalFieldRow label="Document Name:" class="md:col-span-1">
            <input v-model="localForm.document_name" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
          </ModalFieldRow>

          <ModalFieldRow v-if="localForm.source_type === 'template'" label="Contract:" class="md:col-span-2">
            <select v-model="localForm.template_key" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
              <option value="">Select contract</option>
              <option v-for="template in templates" :key="template.key" :value="template.key">
                {{ template.label }}
              </option>
            </select>
          </ModalFieldRow>

          <ModalFieldRow v-else label="Upload File:" class="md:col-span-2">
            <input type="file" accept=".pdf,.doc,.docx" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" @change="onFileChange" />
          </ModalFieldRow>

          <ModalFieldRow label="Subject:" class="md:col-span-2">
            <input v-model="localForm.subject" class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
          </ModalFieldRow>

          <ModalFieldRow label="Message:" class="md:col-span-2">
            <textarea v-model="localForm.message" rows="4" class="w-full resize-y rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs" />
          </ModalFieldRow>
        </div>
      </div>

      <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
        {{ error }}
      </div>
    </div>
  </BaseAdminModal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import BaseAdminModal from './BaseAdminModal.vue'
import ModalFieldRow from './ModalFieldRow.vue'

const props = defineProps({
  open: Boolean,
  loading: { type: Boolean, default: false },
  sending: { type: Boolean, default: false },
  templates: { type: Array, default: () => [] },
  form: {
    type: Object,
    default: () => ({
      source_type: 'template',
      template_key: '',
      recipient_name: '',
      recipient_email: '',
      document_name: 'Lead Contract',
      subject: '',
      message: '',
      file: null,
    }),
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'send', 'update:form'])

const localForm = reactive({
  source_type: 'template',
  template_key: '',
  recipient_name: '',
  recipient_email: '',
  document_name: 'Lead Contract',
  subject: '',
  message: '',
  file: null,
})

watch(
  () => props.form,
  (value) => {
    Object.assign(localForm, {
      source_type: value?.source_type || 'template',
      template_key: value?.template_key || '',
      recipient_name: value?.recipient_name || '',
      recipient_email: value?.recipient_email || '',
      document_name: value?.document_name || 'Lead Contract',
      subject: value?.subject || '',
      message: value?.message || '',
      file: value?.file || null,
    })
  },
  { immediate: true, deep: true }
)

watch(
  localForm,
  (value) => {
    emit('update:form', { ...value })
  },
  { deep: true }
)

function onFileChange(event) {
  const file = event?.target?.files?.[0] || null
  localForm.file = file
}
</script>
