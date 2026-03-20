<template>
  <div v-if="open" class="fixed inset-0 z-[1000]">
    <div class="absolute inset-0 bg-black/40" @click="onClose"></div>

    <div class="absolute inset-0 flex items-end justify-center sm:items-center sm:p-3">
      <div
          ref="modalRef"
          class="relative flex h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-xl sm:h-auto sm:rounded-xl"
          :class="desktopModalClass"
          :style="desktopModalStyle"
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

        <template v-if="isDesktop">
          <div
              class="absolute inset-y-0 right-0 z-10 hidden w-2 cursor-ew-resize sm:block"
              @mousedown.prevent="startResize('right', $event)"
          ></div>

          <div
              class="absolute inset-x-0 bottom-0 z-10 hidden h-2 cursor-ns-resize sm:block"
              @mousedown.prevent="startResize('bottom', $event)"
          ></div>

          <div
              class="absolute bottom-0 right-0 z-20 hidden h-4 w-4 cursor-nwse-resize sm:block"
              title="Resize"
              @mousedown.prevent="startResize('corner', $event)"
          >
            <div class="absolute bottom-1 right-1 h-2.5 w-2.5 border-b-2 border-r-2 border-gray-300"></div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const modalSizeMemory = new Map()

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Edit Record' },
  loading: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  deleting: { type: Boolean, default: false },
  recordId: { type: [Number, String, null], default: null },
  saveLabel: { type: String, default: 'Save' },
  sizeMemoryKey: { type: String, default: '' },
})

const emit = defineEmits(['close', 'save', 'delete'])

const confirmingDelete = ref(false)
const modalRef = ref(null)
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)
const viewportHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 900)
const modalWidth = ref(1088)
const modalHeight = ref(null)
const resizeState = ref(null)

const DESKTOP_BREAKPOINT = 640
const DEFAULT_WIDTH = 1088
const DEFAULT_MAX_HEIGHT_RATIO = 0.86
const DEFAULT_MIN_HEIGHT = 420
const DEFAULT_MIN_WIDTH = 720
const DEFAULT_STORAGE_PREFIX = 'base-admin-modal-size:'

const isDesktop = computed(() => viewportWidth.value >= DESKTOP_BREAKPOINT)

const desktopModalClass = computed(() => {
  if (!isDesktop.value) {
    return ''
  }

  return 'sm:min-w-[720px] sm:min-h-[420px]'
})

const desktopModalStyle = computed(() => {
  if (!isDesktop.value) {
    return null
  }

  const maxWidth = Math.max(820, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(520, Math.floor(viewportHeight.value - 48))
  const width = Math.min(Math.max(modalWidth.value, DEFAULT_MIN_WIDTH), maxWidth)
  const height = modalHeight.value == null
      ? Math.min(Math.max(Math.floor(viewportHeight.value * DEFAULT_MAX_HEIGHT_RATIO), DEFAULT_MIN_HEIGHT), maxHeight)
      : Math.min(Math.max(modalHeight.value, DEFAULT_MIN_HEIGHT), maxHeight)

  return {
    width: `${width}px`,
    maxWidth: `${maxWidth}px`,
    height: `${height}px`,
    maxHeight: `${maxHeight}px`,
  }
})

function getStorageKey() {
  const key = String(props.sizeMemoryKey || '').trim()
  return key ? `${DEFAULT_STORAGE_PREFIX}${key}` : ''
}

function buildCurrentSizePayload() {
  return {
    width: modalWidth.value,
    height: modalHeight.value,
  }
}

function writeRememberedSize(payload) {
  const storageKey = getStorageKey()
  if (!storageKey) {
    return
  }

  modalSizeMemory.set(storageKey, payload)

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(storageKey, JSON.stringify(payload))
  }
}

function readRememberedSize() {
  const storageKey = getStorageKey()
  if (!storageKey) {
    return null
  }

  const inMemory = modalSizeMemory.get(storageKey)
  if (inMemory && typeof inMemory === 'object') {
    return inMemory
  }

  if (typeof window === 'undefined') {
    return null
  }

  const raw = window.localStorage.getItem(storageKey)
  if (!raw) {
    return null
  }

  try {
    const payload = JSON.parse(raw)
    if (payload && typeof payload === 'object') {
      modalSizeMemory.set(storageKey, payload)
      return payload
    }
  } catch (_error) {
    return null
  }

  return null
}

function persistModalSize() {
  if (!isDesktop.value) {
    return
  }

  writeRememberedSize(buildCurrentSizePayload())
}

function syncViewport() {
  viewportWidth.value = window.innerWidth
  viewportHeight.value = window.innerHeight

  if (!isDesktop.value) {
    modalHeight.value = null
    return
  }

  const maxWidth = Math.max(820, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(520, Math.floor(viewportHeight.value - 48))

  modalWidth.value = Math.min(Math.max(modalWidth.value, DEFAULT_MIN_WIDTH), maxWidth)

  if (modalHeight.value != null) {
    modalHeight.value = Math.min(Math.max(modalHeight.value, DEFAULT_MIN_HEIGHT), maxHeight)
  }
}

function resetModalSize() {
  modalWidth.value = Math.min(DEFAULT_WIDTH, Math.max(DEFAULT_MIN_WIDTH, viewportWidth.value - 48))
  modalHeight.value = null
}

function restoreRememberedSize() {
  if (!isDesktop.value) {
    return false
  }

  const payload = readRememberedSize()
  if (!payload) {
    return false
  }

  const maxWidth = Math.max(820, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(520, Math.floor(viewportHeight.value - 48))
  const nextWidth = Number(payload?.width)
  const nextHeight = Number(payload?.height)

  if (Number.isFinite(nextWidth) && nextWidth > 0) {
    modalWidth.value = Math.min(Math.max(nextWidth, DEFAULT_MIN_WIDTH), maxWidth)
  }

  if (Number.isFinite(nextHeight) && nextHeight > 0) {
    modalHeight.value = Math.min(Math.max(nextHeight, DEFAULT_MIN_HEIGHT), maxHeight)
  } else {
    modalHeight.value = null
  }

  return true
}

async function applyOpenSizeState() {
  if (!props.open) {
    return
  }

  await nextTick()
  syncViewport()

  const restored = restoreRememberedSize()
  if (!restored) {
    resetModalSize()
  }
}

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
  persistModalSize()
  confirmingDelete.value = false
  stopResize()
  emit('close')
}

function startResize(direction, event) {
  if (!isDesktop.value || !modalRef.value) {
    return
  }

  resizeState.value = {
    direction,
    startX: event.clientX,
    startY: event.clientY,
    startWidth: modalRef.value.offsetWidth,
    startHeight: modalRef.value.offsetHeight,
  }

  window.addEventListener('mousemove', onResizeMove)
  window.addEventListener('mouseup', stopResize)
}

function onResizeMove(event) {
  if (!resizeState.value) {
    return
  }

  const maxWidth = Math.max(820, Math.floor(viewportWidth.value - 48))
  const maxHeight = Math.max(520, Math.floor(viewportHeight.value - 48))
  const nextWidth = resizeState.value.startWidth + (event.clientX - resizeState.value.startX)
  const nextHeight = resizeState.value.startHeight + (event.clientY - resizeState.value.startY)

  if (resizeState.value.direction === 'right' || resizeState.value.direction === 'corner') {
    modalWidth.value = Math.min(Math.max(nextWidth, DEFAULT_MIN_WIDTH), maxWidth)
  }

  if (resizeState.value.direction === 'bottom' || resizeState.value.direction === 'corner') {
    modalHeight.value = Math.min(Math.max(nextHeight, DEFAULT_MIN_HEIGHT), maxHeight)
  }

  persistModalSize()
}

function stopResize() {
  if (!resizeState.value) {
    return
  }

  resizeState.value = null
  persistModalSize()
  window.removeEventListener('mousemove', onResizeMove)
  window.removeEventListener('mouseup', stopResize)
}

watch(
    () => props.open,
    async (value) => {
      if (!value) {
        confirmingDelete.value = false
        stopResize()
        return
      }

      await applyOpenSizeState()
    }
)

onMounted(async () => {
  syncViewport()
  window.addEventListener('resize', syncViewport)
  await applyOpenSizeState()
})

onBeforeUnmount(() => {
  persistModalSize()
  stopResize()
  window.removeEventListener('resize', syncViewport)
})
</script>