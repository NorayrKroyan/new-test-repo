<template>
  <div :class="$attrs.class">
    <div class="grid grid-cols-[160px_minmax(0,1fr)] items-center gap-2">
      <div
          class="text-right pr-1 text-sm font-semibold text-gray-800"
          :class="{ 'whitespace-nowrap': nowrapLabel }"
      >
        {{ label }}
      </div>

      <div class="min-w-0">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  nowrapLabel: { type: Boolean, default: false },
})
</script>