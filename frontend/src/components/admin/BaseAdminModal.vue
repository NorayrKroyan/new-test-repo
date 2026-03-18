<template>
  <div v-if="open" class="fixed inset-0 z-[1000]">
    <div class="absolute inset-0 bg-black/40" @click="onClose"></div>

    <div class="absolute inset-0 flex items-end justify-center sm:items-center sm:p-3">
      <div
          class="relative flex h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-xl sm:h-auto sm:max-h-[86vh] sm:max-w-[68rem] sm:rounded-xl"
      >
        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 sm:px-4">
          <div class="min-w-0 pr-2 text-sm font-semibold sm:text-base">
            {{ title }}
          </div>

          <button
              class="shrink-0 rounded-md px-2 py-0.5 text-xs hover:bg-gray-100"
              @click="onClose"
          >
            ✕
          </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 sm:px-4">
          <div v-if="loading" class="py-8 text-center text-sm text-gray-500">Loading...</div>

          <div v-else class="space-y-2">
            <slot />
          </div>
        </div>

        <div class="border-t border-gray-200 px-3 py-2 sm:px-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <button
                  v-if="recordId"
                  class="w-full rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:opacity-50 sm:w-auto"
                  :disabled="saving || deleting"
                  @click="onDeleteClick"
              >
                <span v-if="!confirmingDelete">Delete</span>
                <span v-else>Confirm delete</span>
              </button>

              <button
                  v-if="recordId && confirmingDelete"
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50 sm:w-auto"
                  :disabled="saving || deleting"
                  @click="cancelDelete"
              >
                Cancel delete
              </button>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
              <button
                  class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50 sm:w-auto"
                  :disabled="saving || deleting"
                  @click="onClose"
              >
                Cancel
              </button>

              <button
                  class="w-full rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-50 sm:w-auto"
                  :disabled="saving || deleting"
                  @click="$emit('save')"
              >
                {{ saving ? 'Saving...' : saveLabel }}
              </button>
            </div>
          </div>
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