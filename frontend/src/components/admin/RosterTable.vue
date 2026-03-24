<template>
  <div class="overflow-x-auto">
    <table class="min-w-full border-separate border-spacing-0 text-xs">
      <thead>
      <tr>
        <th class="th-cell rounded-tl-lg">Slot</th>
        <th class="th-cell">Carrier</th>
        <th class="th-cell">Driver</th>
        <th class="th-cell rounded-tr-lg">Status</th>
      </tr>
      </thead>

      <tbody>
      <tr
          v-for="row in rows"
          :key="row.id"
          :class="rowClass(row)"
      >
        <td class="td-cell">
          <button
              type="button"
              class="position-edit-link"
              @click="$emit('edit', row)"
          >
            <span class="slot-badge" :class="slotBadgeClass(row.slot_type)">
              {{ row.slot_label || slotLabel(row.slot_type, row.slot_number) }}
            </span>
          </button>
        </td>

        <td class="td-cell">
          {{ displayValue(row.carrier_name) }}
        </td>

        <td class="td-cell">
          {{ displayValue(row.driver_name) }}
        </td>

        <td class="td-cell">
          <span class="status-badge" :class="statusBadgeClass(row.status_key || row.status)">
            {{ row.status_label || statusLabel(row.status_key || row.status, row.slot_type) }}
          </span>
        </td>
      </tr>

      <tr v-if="!rows.length">
        <td colspan="4" class="empty-cell">
          No roster rows found.
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['edit'])

function displayValue(value) {
  return value === null || value === undefined || String(value).trim() === '' ? '—' : String(value)
}

function slotLabel(value, slotNumber) {
  return String(value || '').toLowerCase() === 'spare'
      ? `On-Call ${slotNumber || ''}`.trim()
      : `Position ${slotNumber || ''}`.trim()
}

function slotBadgeClass(value) {
  return String(value || '').toLowerCase() === 'spare'
      ? 'slot-badge-spare'
      : 'slot-badge-primary'
}

function statusLabel(value, slotType) {
  const normalized = String(value || '').trim().toLowerCase()

  if (normalized === 'ready') return 'Ready'
  if (normalized === 'pending_paperwork') return 'Pending paperwork'
  if (normalized === 'open_alternate') return 'Open on-call'

  return String(slotType || '').toLowerCase() === 'spare' ? 'Open on-call' : 'Open'
}

function statusBadgeClass(value) {
  const normalized = String(value || '').trim().toLowerCase()

  if (normalized === 'ready') return 'status-badge-ready'
  if (normalized === 'pending_paperwork') return 'status-badge-pending'
  if (normalized === 'open_alternate') return 'status-badge-alternate'

  return 'status-badge-open'
}

function rowClass(row) {
  const normalized = String(row?.status_key || row?.status || '').trim().toLowerCase()

  if (normalized === 'ready') return 'bg-emerald-50/60'
  if (normalized === 'pending_paperwork') return 'bg-amber-50/60'
  if (normalized === 'open_alternate') return 'bg-indigo-50/60'

  return 'bg-white'
}
</script>

<style scoped>
.th-cell {
  border-bottom: 1px solid #e5e7eb;
  background: #f8fafc;
  padding: 8px 10px;
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  white-space: nowrap;
}

.td-cell {
  border-bottom: 1px solid #e5e7eb;
  padding: 8px 10px;
  color: #111827;
  vertical-align: middle;
  white-space: nowrap;
  text-align: center;
}

.empty-cell {
  padding: 14px 10px;
  text-align: center;
  color: #64748b;
  font-size: 12px;
}

.slot-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: 1px solid transparent;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.1;
}

.slot-badge-primary {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.slot-badge-spare {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #4338ca;
}

.status-badge-ready {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.status-badge-pending {
  background: #fffbeb;
  border-color: #fcd34d;
  color: #c2410c;
}

.status-badge-open {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.status-badge-alternate {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #4338ca;
}

.position-edit-link {
  border: 0;
  background: transparent;
  padding: 0;
  cursor: pointer;
  font: inherit;
}

.position-edit-link:hover .slot-badge {
  filter: brightness(0.97);
}
</style>