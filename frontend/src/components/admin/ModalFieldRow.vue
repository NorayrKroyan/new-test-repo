<template>
  <div :class="$attrs.class">
    <div class="grid grid-cols-1 gap-1 md:grid-cols-[160px_minmax(0,1fr)] md:items-center md:gap-2">
      <div
          class="text-sm font-semibold text-gray-800 md:pr-1 md:text-right"
          :class="{ 'md:whitespace-nowrap': nowrapLabel }"
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