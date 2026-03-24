<template>
  <div class="help-root">
    <button type="button" class="help-trigger" tabindex="0" aria-label="Field help">?</button>
    <div class="help-popup" role="tooltip">
      {{ text }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    default: '',
  },
})
</script>

<style scoped>
.help-root {
  position: relative;
  display: inline-flex;
  flex: 0 0 auto;
}

.help-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border: 1px solid #cbd5e1;
  border-radius: 9999px;
  background: #f8fafc;
  color: #475569;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  cursor: help;
}

.help-popup {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 80;
  width: 280px;
  max-width: min(280px, 70vw);
  padding: 8px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #0f172a;
  color: #fff;
  font-size: 11px;
  line-height: 1.35;
  white-space: normal;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.22);
  opacity: 0;
  visibility: hidden;
  transform: translateY(4px);
  transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s ease;
  pointer-events: none;
}

.help-root:hover .help-popup,
.help-root:focus-within .help-popup {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
</style>