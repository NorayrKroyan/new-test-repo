<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Carriers</h1>
      </div>

      <div class="flex gap-2">
        <input
            v-model="q"
            class="h-11 w-72 rounded-xl border border-slate-300 bg-white px-3"
            placeholder="Search carrier"
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
            <th class="px-4 py-3">Company</th>
            <th class="px-4 py-3">Contact</th>
            <th class="px-4 py-3">Location</th>
            <th class="px-4 py-3">Fleet</th>
            <th class="px-4 py-3">Status</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <button class="font-medium text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.company_name || row.contact_name || '—' }}
              </button>
            </td>
            <td class="px-4 py-3">{{ row.contact_name || '—' }}</td>
            <td class="px-4 py-3">{{ row.city || '—' }}, {{ row.state || '—' }}</td>
            <td class="px-4 py-3">{{ row.truck_count || 0 }} / {{ row.trailer_count || 0 }}</td>
            <td class="px-4 py-3">{{ row.status || 'pending_review' }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <CarrierModal
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
import CarrierModal from '../../components/admin/CarrierModal.vue';
import { deleteCarrier, fetchCarriers, saveCarrier } from '../../api/admin';

const q = ref('');
const rows = ref([]);
const open = ref(false);
const saving = ref(false);
const deleting = ref(false);

const form = reactive({
  id: null,
  company_name: '',
  contact_name: '',
  email: '',
  phone: '',
  city: '',
  state: '',
  usdot: '',
  mc_number: '',
  carrier_class: '',
  insurance_status: '',
  truck_count: '',
  trailer_count: '',
  status: 'pending_review',
  notes: '',
});

function resetForm() {
  Object.assign(form, {
    id: null,
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    city: '',
    state: '',
    usdot: '',
    mc_number: '',
    carrier_class: '',
    insurance_status: '',
    truck_count: '',
    trailer_count: '',
    status: 'pending_review',
    notes: '',
  });
}

async function loadRows() {
  const data = await fetchCarriers({ q: q.value });
  rows.value = data.data || [];
}

function openCreate() {
  resetForm();
  open.value = true;
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    company_name: row.company_name || '',
    contact_name: row.contact_name || '',
    email: row.email || '',
    phone: row.phone || '',
    city: row.city || '',
    state: row.state || '',
    usdot: row.usdot || '',
    mc_number: row.mc_number || '',
    carrier_class: row.carrier_class || '',
    insurance_status: row.insurance_status || '',
    truck_count: row.truck_count || '',
    trailer_count: row.trailer_count || '',
    status: row.status || 'pending_review',
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
    await saveCarrier(form, form.id);
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
    await deleteCarrier(form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    deleting.value = false;
  }
}

onMounted(loadRows);
</script>