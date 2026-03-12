<template>
  <AdminLayout>
    <div class="mb-3 flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Leads</h1>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row">
        <input
            v-model="q"
            class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 sm:w-72"
            placeholder="Search lead"
            @keyup.enter="loadRows"
        />
        <button
            class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white"
            @click="openCreate"
        >
          Add
        </button>
      </div>
    </div>

    <!-- Mobile cards -->
    <div class="space-y-2 md:hidden">
      <div
          v-for="row in rows"
          :key="row.id"
          class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
      >
        <div class="flex items-start justify-between gap-2">
          <button class="text-left font-semibold leading-5 text-sky-700 hover:underline" @click="editRow(row)">
            {{ row.full_name || '—' }}
          </button>

          <div class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
            {{ row.lead_status || '—' }}
          </div>
        </div>

        <div class="mt-2 grid grid-cols-1 gap-1 text-sm leading-5 text-slate-700">
          <div class="break-all"><span class="font-medium">Email:</span> {{ row.email || '—' }}</div>
          <div><span class="font-medium">Platform:</span> {{ row.platform || '—' }}</div>
          <div><span class="font-medium">City:</span> {{ row.city || '—' }}</div>
          <div><span class="font-medium">Insurance:</span> {{ row.insurance_answer || '—' }}</div>
        </div>

        <button
            v-if="row.lead_status !== 'converted_to_carrier'"
            class="mt-2 w-full rounded-xl border border-sky-300 px-3 py-1.5 text-sm font-medium text-sky-700 hover:bg-sky-50"
            @click="convertRow(row.id)"
        >
          Convert to Carrier
        </button>
      </div>

      <div
          v-if="!rows.length"
          class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-sm text-slate-500 shadow-sm"
      >
        No leads found.
      </div>
    </div>

    <!-- Desktop table -->
    <div class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
          <tr class="border-b border-slate-200 text-slate-500">
            <th class="px-4 py-2.5">Lead</th>
            <th class="px-4 py-2.5">Email</th>
            <th class="px-4 py-2.5">Platform</th>
            <th class="px-4 py-2.5">City</th>
            <th class="px-4 py-2.5">Insurance</th>
            <th class="px-4 py-2.5">Status</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100 align-top">
            <td class="px-4 py-2.5">
              <button class="font-medium leading-5 text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.full_name || '—' }}
              </button>
            </td>
            <td class="px-4 py-2.5 text-slate-600">
              <div class="max-w-[260px] break-all leading-5">
                {{ row.email || '—' }}
              </div>
            </td>
            <td class="px-4 py-2.5 leading-5">{{ row.platform || '—' }}</td>
            <td class="px-4 py-2.5 leading-5">{{ row.city || '—' }}</td>
            <td class="px-4 py-2.5 leading-5">{{ row.insurance_answer || '—' }}</td>
            <td class="px-4 py-2.5">
              <div class="leading-5">{{ row.lead_status || '—' }}</div>
              <button
                  v-if="row.lead_status !== 'converted_to_carrier'"
                  class="mt-0.5 text-xs font-medium text-sky-700 hover:underline"
                  @click="convertRow(row.id)"
              >
                Convert to Carrier
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <LeadModal
        :open="open"
        :saving="saving"
        :deleting="deleting"
        :form="form"
        @close="closeModal"
        @save="saveRow"
        @delete="deleteCurrent"
    />
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AdminLayout from '../../layouts/AdminLayout.vue'
import LeadModal from '../../components/admin/LeadModal.vue'
import { convertLeadToCarrier, deleteLead, fetchLeads, saveLead } from '../../api/admin'

const q = ref('')
const rows = ref([])
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({
  id: null,
  source_name: '',
  ad_name: '',
  platform: '',
  source_created_at: '',
  lead_date_choice: '',
  insurance_answer: '',
  full_name: '',
  email: '',
  phone: '',
  city: '',
  state: '',
  carrier_class: '',
  usdot: '',
  truck_count: '',
  trailer_count: '',
  lead_status: 'new',
  notes: '',
})

function resetForm() {
  Object.assign(form, {
    id: null,
    source_name: '',
    ad_name: '',
    platform: '',
    source_created_at: '',
    lead_date_choice: '',
    insurance_answer: '',
    full_name: '',
    email: '',
    phone: '',
    city: '',
    state: '',
    carrier_class: '',
    usdot: '',
    truck_count: '',
    trailer_count: '',
    lead_status: 'new',
    notes: '',
  })
}

async function loadRows() {
  const data = await fetchLeads({ q: q.value })
  rows.value = data.data || []
}

function openCreate() {
  resetForm()
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    source_name: row.source_name || '',
    ad_name: row.ad_name || '',
    platform: row.platform || '',
    source_created_at: row.source_created_at || '',
    lead_date_choice: row.lead_date_choice || '',
    insurance_answer: row.insurance_answer || '',
    full_name: row.full_name || '',
    email: row.email || '',
    phone: row.phone || '',
    city: row.city || '',
    state: row.state || '',
    carrier_class: row.carrier_class || '',
    usdot: row.usdot || '',
    truck_count: row.truck_count || '',
    trailer_count: row.trailer_count || '',
    lead_status: row.lead_status || 'new',
    notes: row.notes || '',
  })

  open.value = true
}

function closeModal() {
  open.value = false
}

async function saveRow() {
  saving.value = true
  try {
    await saveLead(form, form.id)
    open.value = false
    resetForm()
    await loadRows()
  } finally {
    saving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  try {
    await deleteLead(form.id)
    open.value = false
    resetForm()
    await loadRows()
  } finally {
    deleting.value = false
  }
}

async function convertRow(id) {
  await convertLeadToCarrier(id)
  await loadRows()
}

onMounted(loadRows)
</script>