<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Leads</h1>
      </div>

      <div class="flex gap-2">
        <input
            v-model="q"
            class="h-11 w-72 rounded-xl border border-slate-300 bg-white px-3"
            placeholder="Search lead"
            @keyup.enter="loadRows"
        />
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white" @click="openCreate">Add</button>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
          <tr class="border-b border-slate-200 text-slate-500">
            <th class="px-4 py-3">Lead</th>
            <th class="px-4 py-3">Platform</th>
            <th class="px-4 py-3">City</th>
            <th class="px-4 py-3">Insurance</th>
            <th class="px-4 py-3">Status</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <button class="font-medium text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.full_name || '—' }}
              </button>
              <div class="text-slate-500">{{ row.email || '—' }}</div>
            </td>
            <td class="px-4 py-3">{{ row.platform || '—' }}</td>
            <td class="px-4 py-3">{{ row.city || '—' }}</td>
            <td class="px-4 py-3">{{ row.insurance_answer || '—' }}</td>
            <td class="px-4 py-3">
              <div>{{ row.lead_status || '—' }}</div>
              <button
                  v-if="row.lead_status !== 'converted_to_carrier'"
                  class="mt-1 text-xs font-medium text-sky-700 hover:underline"
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
import { onMounted, reactive, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import LeadModal from '../../components/admin/LeadModal.vue';
import { convertLeadToCarrier, deleteLead, fetchLeads, saveLead } from '../../api/admin';

const q = ref('');
const rows = ref([]);
const open = ref(false);
const saving = ref(false);
const deleting = ref(false);

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
});

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
  });
}

async function loadRows() {
  const data = await fetchLeads({ q: q.value });
  rows.value = data.data || [];
}

function openCreate() {
  resetForm();
  open.value = true;
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
  });

  open.value = true;
}

function closeModal() {
  open.value = false;
}

async function saveRow() {
  saving.value = true;
  try {
    await saveLead(form, form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    saving.value = false;
  }
}

async function deleteCurrent() {
  if (!form.id) return;

  deleting.value = true;
  try {
    await deleteLead(form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    deleting.value = false;
  }
}

async function convertRow(id) {
  await convertLeadToCarrier(id);
  await loadRows();
}

onMounted(loadRows);
</script>