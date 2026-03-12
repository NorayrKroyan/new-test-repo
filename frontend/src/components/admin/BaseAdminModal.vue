<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="onClose"></div>

    <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
        <div class="text-lg font-semibold">{{ title }}</div>
        <button class="rounded-lg px-2 py-1 text-sm hover:bg-gray-100" @click="onClose">✕</button>
      </div>

      <div class="max-h-[75vh] overflow-y-auto px-5 py-4">
        <div v-if="loading" class="py-10 text-center text-gray-500">Loading...</div>

        <div v-else class="space-y-3">
          <slot />
        </div>
      </div>

      <div class="flex items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
        <div class="flex items-center">
          <button
              v-if="recordId"
              class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="onDeleteClick"
          >
            <span v-if="!confirmingDelete">Delete</span>
            <span v-else>Confirm delete</span>
          </button>

          <button
              v-if="recordId && confirmingDelete"
              class="ml-2 rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="cancelDelete"
          >
            Cancel
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="onClose"
          >
            Cancel
          </button>

          <button
              class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
              :disabled="saving || deleting"
              @click="$emit('save')"
          >
            {{ saving ? 'Saving...' : saveLabel }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Edit Record' },
  loading: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  deleting: { type: Boolean, default: false },
  recordId: { type: [Number, String, null], default: null },
  saveLabel: { type: String, default: 'Save' },
})

const emit = defineEmits(['close', 'save', 'delete'])

const confirmingDelete = ref(false)

function onDeleteClick() {
  if (!confirmingDelete.value) {
    confirmingDelete.value = true
    return
  }

  emit('delete')
  confirmingDelete.value = false
}

function cancelDelete() {
  confirmingDelete.value = false
}

function onClose() {
  confirmingDelete.value = false
  emit('close')
}

watch(
    () => props.open,
    (v) => {
      if (!v) confirmingDelete.value = false
    }
)
</script>